<?php

namespace App\Command\MakeDCrudCommand;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Inflector\EnglishInflector;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

#[AsCommand(
    name: 'make:d-crud',
    description: 'Creates CRUD in the Admin namespace for an entity',
)]
class MakeDCrudCommand extends Command
{
    private $em;
    private $projectDir;
    private $inflector;
    private $fs;
    private $propertyInfo;
    private $commandDir;

    public function __construct(EntityManagerInterface $em, string $projectDir)
    {
        parent::__construct();
        $this->em = $em;
        $this->projectDir = $projectDir;
        $this->inflector = new EnglishInflector();
        $this->fs = new Filesystem();
        $this->propertyInfo = new PropertyInfoExtractor([new ReflectionExtractor()]);
        $this->commandDir = dirname(__FILE__);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('entity', InputArgument::REQUIRED, 'The entity name (e.g. "User")');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entityName = $input->getArgument('entity');
        $entityClass = "App\\Entity\\$entityName";

        // Generamos automáticamente el namespace como Admin/{EntityName}
        $controllerNamespace = "Admin\\" . $entityName;

        if (!class_exists($entityClass)) {
            $io->error(sprintf('Entity "%s" does not exist in App\\Entity namespace.', $entityName));
            return Command::FAILURE;
        }

        // Parse entity information
        $entityVarSingular = lcfirst($entityName);
        $entityVarPlural = lcfirst($this->inflector->pluralize($entityName)[0]);
        $entityPluralName = $this->inflector->pluralize($entityName)[0];

        // Crear el nombre del controlador automáticamente
        $controllerName = $entityName . 'Controller';

        // Construir la ruta completa del controlador
        $fullControllerNamespace = 'App\\Controller\\' . str_replace('/', '\\', $controllerNamespace);

        // Create directories for controller and form
        $controllerDir = $this->projectDir . '/src/Controller/' . str_replace('\\', '/', $controllerNamespace);
        $formDir = $this->projectDir . '/src/Form/' . str_replace('\\', '/', $controllerNamespace);

        // Crear directorio para las plantillas Twig
        $templatePath = strtolower(str_replace('\\', '/', $controllerNamespace));
        $templateDir = $this->projectDir . '/templates/' . $templatePath;

        $this->fs->mkdir($controllerDir);
        $this->fs->mkdir($formDir);
        $this->fs->mkdir($templateDir);

        // Obtener propiedades de la entidad
        $entityFields = $this->getEntityFields($entityClass);
        $hasTimestamps = $this->entityHasTimestamps($entityClass);
        $hasIsActive = $this->entityHasIsActive($entityClass);

        // Ruta base del controlador - nombre en singular de la entidad
        $routePath = $entityVarSingular;

        // Prefijo para nombres de rutas: admin_entitysingular (ej: admin_contact)
        $parts = explode('\\', $controllerNamespace);
        $firstPart = strtolower($parts[0]);
        $routePrefix = $firstPart . '_' . $entityVarSingular;

        // Preparar relaciones para el form
        $relationUseStatements = '';
        foreach ($entityFields as $field => $type) {
            if (is_array($type) && $type['type'] === 'relation') {
                $relationClass = $type['relation_class'];
                if (class_exists($relationClass)) {
                    $relationUseStatements .= "use $relationClass;\n";
                }
            }
        }

        // Generate controller
        $this->generateController(
            $controllerDir,
            $controllerName,
            $fullControllerNamespace,
            $entityClass,
            $entityName,
            $entityVarSingular,
            $entityVarPlural,
            $entityPluralName,
            $hasTimestamps,
            $hasIsActive,
            $entityFields,
            $templatePath,
            $routePath,
            $routePrefix
        );

        $formTypeNamespace = str_replace("App\Controller\\", "", $controllerNamespace);

        // Generate form type
        $this->generateFormType(
            $formDir,
            'App\\Form\\' . str_replace('/', '\\', $formTypeNamespace),
            $entityClass,
            $entityName,
            $entityFields,
            $relationUseStatements
        );

        // Generate templates
        $this->generateTemplates(
            $templateDir,
            $templatePath,
            $entityVarSingular,
            $entityVarPlural,
            $entityName,
            $entityPluralName,
            $entityFields,
            $hasTimestamps,
            $hasIsActive,
            $routePrefix
        );

        $io->success(sprintf('CRUD de "%s" ha sido generado en: "%s"', $entityName, $fullControllerNamespace));
        $io->note(sprintf('Las Templates han sido creadas en: "%s"', $templateDir));
        $io->note(sprintf('El Controller ha sido creado en: "%s"', $controllerDir . '/' . $controllerName . '.php'));
        $io->note(sprintf('El Form ha sido creado en: "%s"', $formDir . '/' . $entityName . 'Type.php'));

        return Command::SUCCESS;
    }

    private function getEntityFields(string $entityClass): array
    {
        $fields = [];
        $properties = $this->propertyInfo->getProperties($entityClass);
        $metadata = $this->em->getClassMetadata($entityClass);

        if ($properties) {
            foreach ($properties as $property) {
                // Skip common fields that are automatically handled
                // También excluimos 'active' para evitar la duplicación con 'isActive'
                if (in_array($property, ['id', 'createdAt', 'updatedAt', 'isActive', 'active'])) {
                    continue;
                }

                // Check if the property is a relation
                if ($metadata->hasAssociation($property)) {
                    $targetEntity = $metadata->getAssociationTargetClass($property);
                    $fields[$property] = [
                        'type' => 'relation',
                        'relation_class' => $targetEntity,
                        'relation_type' => $metadata->isSingleValuedAssociation($property) ? 'single' : 'collection'
                    ];
                } else {
                    $type = $this->propertyInfo->getTypes($entityClass, $property);
                    $fields[$property] = $type ? $type[0]->getBuiltinType() : 'string';
                }
            }
        }

        return $fields;
    }

    private function entityHasTimestamps(string $entityClass): bool
    {
        $properties = $this->propertyInfo->getProperties($entityClass);
        return $properties && (in_array('createdAt', $properties) || in_array('updatedAt', $properties));
    }

    private function entityHasIsActive(string $entityClass): bool
    {
        $properties = $this->propertyInfo->getProperties($entityClass);
        // Verificar si existe la propiedad isActive pero no active
        return $properties && (in_array('isActive', $properties) || in_array('active', $properties));
    }

    private function generateController(
        string $dir,
        string $controllerName,
        string $namespace,
        string $entityClass,
        string $entityName,
        string $entityVarSingular,
        string $entityVarPlural,
        string $entityPluralName,
        bool   $hasTimestamps,
        bool   $hasIsActive,
        array  $entityFields,
        string $templatePath,
        string $routePath,
        string $routePrefix
    ): void
    {
        $templateFile = $this->commandDir . '/controller/controller.php.tpl';
        if (!file_exists($templateFile)) {
            throw new \RuntimeException(sprintf('Controller template file "%s" not found.', $templateFile));
        }

        $template = file_get_contents($templateFile);

        // Reemplazos básicos
        $template = str_replace('{{controller_namespace}}', $namespace, $template);
        $template = str_replace('{{entity_full_class_name}}', $entityClass, $template);
        $template = str_replace('{{entity_name}}', $entityName, $template);
        $template = str_replace('{{entity_plural_name}}', $entityPluralName, $template);
        $template = str_replace('{{controller_name}}', $controllerName, $template);
        $template = str_replace('{{route_path}}', $routePath, $template);
        $template = str_replace('{{route_prefix}}', $routePrefix, $template);
        $template = str_replace('{{entity_var_singular}}', $entityVarSingular, $template);
        $template = str_replace('{{entity_var_plural}}', $entityVarPlural, $template);
        $template = str_replace('{{template_path}}', $templatePath, $template);

        // Condicionales para timestamps
        $template = $this->processConditionals($template, 'has_timestamps', $hasTimestamps);

        // Condicionales para isActive
        $template = $this->processConditionals($template, 'has_is_active', $hasIsActive);

        // Ajustar el namespace del FormType
        $formNamespace = 'App\\Form\\' . str_replace('App\\Controller\\', '', str_replace('/', '\\', $namespace));
        $template = str_replace("use App\\Form\\{$entityName}Type;", "use {$formNamespace}\\{$entityName}Type;", $template);

        // Campos para Excel
        $fieldGetters = '';
        foreach ($entityFields as $field => $type) {
            $isBoolean = ($type === 'bool');
            // Determinar el método getter correcto
            $getter = $isBoolean ? "is" . ucfirst($field) : "get" . ucfirst($field);
            $fieldLabel = ucfirst($field);

            if (is_array($type) && $type['type'] === 'relation') {
                $fieldGetters .= "\$" . $entityVarPlural . "[\$i][\"$fieldLabel\"] = \$" . $entityVarPlural . "Array[\$i]->$getter() ? \$" . $entityVarPlural . "Array[\$i]->$getter()->getId() : '';\n            ";
            } else if ($isBoolean) {
                $fieldGetters .= "\$" . $entityVarPlural . "[\$i][\"$fieldLabel\"] = \$" . $entityVarPlural . "Array[\$i]->$getter() ? \"Sí\" : \"No\";\n            ";
            } elseif ($type === 'object' && str_contains(strtolower($field), 'date')) {
                $fieldGetters .= "\$" . $entityVarPlural . "[\$i][\"$fieldLabel\"] = \$" . $entityVarPlural . "Array[\$i]->$getter() ? \$" . $entityVarPlural . "Array[\$i]->$getter()->format(\"d/m/Y H:i:s\") : '';\n            ";
            } elseif ($type === 'array') {
                $fieldGetters .= "\$" . $entityVarPlural . "[\$i][\"$fieldLabel\"] = is_array(\$" . $entityVarPlural . "Array[\$i]->$getter()) ? implode(', ', \$" . $entityVarPlural . "Array[\$i]->$getter()) : '';\n            ";
            } else {
                // Manejar objetos relacionales y convertirlos a string
                $fieldGetters .= "\$" . $entityVarPlural . "[\$i][\"$fieldLabel\"] = \$" . $entityVarPlural . "Array[\$i]->$getter() instanceof \\Stringable ? \$" . $entityVarPlural . "Array[\$i]->$getter()->__toString() : (\$" . $entityVarPlural . "Array[\$i]->$getter() === null ? '' : (string)\$" . $entityVarPlural . "Array[\$i]->$getter());\n            ";
            }
        }

        $template = str_replace('{{entity_fields}}', rtrim($fieldGetters), $template);

        file_put_contents("$dir/$controllerName.php", $template);
    }

    private function generateFormType(
        string $dir,
        string $namespace,
        string $entityClass,
        string $entityName,
        array  $entityFields,
        string $relationUseStatements
    ): void
    {
        $this->fs->mkdir($dir);

        $formFields = '';
        foreach ($entityFields as $field => $type) {
            $formTypeClass = $this->getFormTypeForFieldType($type, $field);
            $constraints = $this->getConstraintsForField($field);

            $formFields .= "            ->add('$field', $formTypeClass, [\n";
            $formFields .= "                'label' => '" . ucfirst($field) . "',\n";

            // Para los campos que son relaciones, configurar apropiadamente
            if (is_array($type) && $type['type'] === 'relation') {
                $targetEntity = $type['relation_class'];
                $shortEntityName = substr($targetEntity, strrpos($targetEntity, '\\') + 1);
                $formFields .= "                'class' => $shortEntityName::class,\n";
                $formFields .= "                'choice_label' => 'id',\n";
                $formFields .= "                'placeholder' => 'Seleccione un $shortEntityName',\n";
                $formFields .= "                'attr' => [\n";
                $formFields .= "                    'class' => 'form-select',\n";
                $formFields .= "                ],\n";
                $formFields .= "                'required' => false,\n";
            } else if ($type === 'object' && !str_contains(strtolower($field), 'date')) {
                $formFields .= "                'required' => false,\n";
                if (str_contains(strtolower($field), 'type') ||
                    str_contains(strtolower($field), 'category') ||
                    str_contains(strtolower($field), 'status')) {
                    $formFields .= "                'choices' => [],\n";
                    $formFields .= "                'placeholder' => 'Seleccione...',\n";
                }
            }

            // Agregar opciones por defecto a campos ChoiceType
            $formTypeClass = $this->getFormTypeForFieldType($type, $field);
            if ($formTypeClass === 'ChoiceType::class') {
                $formFields .= "                'choices' => [\n";
                $formFields .= "                    'Option A' => 'option_a',\n";
                $formFields .= "                    'Option B' => 'option_b',\n";
                $formFields .= "                ],\n";
                $formFields .= "                'placeholder' => 'Seleccione una opción',\n";
                $formFields .= "                'attr' => [\n";
                $formFields .= "                    'class' => 'form-select',\n";
                $formFields .= "                ],\n";
            }

            if (!empty($constraints)) {
                $formFields .= "                'constraints' => [\n";
                $formFields .= "                    $constraints\n";
                $formFields .= "                ],\n";
            }

            $formFields .= "            ])\n";
        }

        if (empty($formFields)) {
            $formFields = "            // Add your entity fields here\n";
            $formFields .= "            // ->add('field1', TextType::class)\n";
            $formFields .= "            // ->add('field2', TextareaType::class)\n";
        }

        $templateFile = $this->commandDir . '/form/form.php.tpl';
        if (!file_exists($templateFile)) {
            throw new \RuntimeException(sprintf('Form template file "%s" not found.', $templateFile));
        }

        $template = file_get_contents($templateFile);
        $template = str_replace('{{form_namespace}}', $namespace, $template);
        $template = str_replace('{{entity_full_class_name}}', $entityClass, $template);
        $template = str_replace('{{entity_name}}', $entityName, $template);
        $template = str_replace('{{form_fields}}', rtrim($formFields), $template);
        $template = str_replace('{{relation_use_statements}}', $relationUseStatements, $template);

        file_put_contents("$dir/{$entityName}Type.php", $template);
    }

    private function getFormTypeForFieldType($type, string $fieldName): string
    {
        // Ahora puede manejar tanto strings como arrays
        if (is_array($type) && $type['type'] === 'relation') {
            if ($type['relation_type'] === 'collection') {
                return 'CollectionType::class';
            } else {
                return 'EntityType::class';
            }
        }

        // El código existente para manejar strings
        switch ($type) {
            case 'bool':
                return 'CheckboxType::class';
            case 'int':
                return 'IntegerType::class';
            case 'float':
                if (str_contains(strtolower($fieldName), 'price') ||
                    str_contains(strtolower($fieldName), 'cost') ||
                    str_contains(strtolower($fieldName), 'amount')) {
                    return 'MoneyType::class';
                }
                if (str_contains(strtolower($fieldName), 'percent') ||
                    str_contains(strtolower($fieldName), 'rate')) {
                    return 'PercentType::class';
                }
                return 'NumberType::class';
            case 'array':
                return 'CollectionType::class';
            case 'object':
                if (str_contains(strtolower($fieldName), 'date') &&
                    str_contains(strtolower($fieldName), 'time')) {
                    return 'DateTimeType::class';
                }
                if (str_contains(strtolower($fieldName), 'date')) {
                    return 'DateType::class';
                }
                if (str_contains(strtolower($fieldName), 'time')) {
                    return 'TimeType::class';
                }
                return 'TextType::class';
            case 'string':
            default:
                if (str_contains(strtolower($fieldName), 'email')) {
                    return 'EmailType::class';
                }
                if (str_contains(strtolower($fieldName), 'password')) {
                    return 'PasswordType::class';
                }
                if (str_contains(strtolower($fieldName), 'url') ||
                    str_contains(strtolower($fieldName), 'website') ||
                    str_contains(strtolower($fieldName), 'link')) {
                    return 'UrlType::class';
                }
                if (str_contains(strtolower($fieldName), 'description') ||
                    str_contains(strtolower($fieldName), 'content') ||
                    str_contains(strtolower($fieldName), 'text') ||
                    str_contains(strtolower($fieldName), 'about')) {
                    return 'TextareaType::class';
                }
                if (str_contains(strtolower($fieldName), 'image') ||
                    str_contains(strtolower($fieldName), 'file') ||
                    str_contains(strtolower($fieldName), 'attachment')) {
                    return 'FileType::class';
                }
                if (str_contains(strtolower($fieldName), 'option') ||
                    str_contains(strtolower($fieldName), 'type') ||
                    str_contains(strtolower($fieldName), 'status') ||
                    str_contains(strtolower($fieldName), 'category')) {
                    return 'ChoiceType::class';
                }
                return 'TextType::class';
        }
    }

    private function getConstraintsForField(string $fieldName): string
    {
        $constraints = [];

        if (in_array($fieldName, ['name', 'title', 'email', 'username', 'password'])) {
            $constraints[] = 'new NotBlank()';
        }

        if (in_array($fieldName, ['email'])) {
            $constraints[] = 'new Email()';
        }

        if (in_array($fieldName, ['password'])) {
            $constraints[] = 'new Length([\'min\' => 6])';
        }

        return implode(', ', $constraints);
    }

    // Método común para procesar condicionales en plantillas
    private function processConditionals(string $content, string $condition, bool $value): string
    {
        if ($value) {
            // Si la condición es verdadera, eliminar las etiquetas condicionales
            $content = preg_replace("/{{#$condition}}(.*?){{\\/$condition}}/s", '$1', $content);
        } else {
            // Si la condición es falsa, eliminar los bloques condicionales
            $content = preg_replace("/{{#$condition}}.*?{{\\/$condition}}/s", '', $content);
        }

        return $content;
    }

    private function generateTemplates(
        string $dir,
        string $templatePath,
        string $entityVarSingular,
        string $entityVarPlural,
        string $entityName,
        string $entityPluralName,
        array  $entityFields,
        bool   $hasTimestamps,
        bool   $hasIsActive,
        string $routePrefix
    ): void
    {
        $this->fs->mkdir($dir);

        // Generate index template
        $this->generateIndexTemplate($dir, $templatePath, $entityVarSingular, $entityVarPlural, $entityName, $entityPluralName, $entityFields, $hasTimestamps, $hasIsActive, $routePrefix);

        // Generate _new template
        $this->generateNewTemplate($dir, $templatePath, $entityVarSingular, $entityName, $entityFields, $routePrefix);

        // Generate _show template
        $this->generateShowTemplate($dir, $templatePath, $entityVarSingular, $entityName, $entityFields, $hasTimestamps, $hasIsActive, $routePrefix);
        // Generate _edit template
        $this->generateEditTemplate($dir, $templatePath, $entityVarSingular, $entityName, $entityFields, $routePrefix);

        // Generate _delete template
        $this->generateDeleteTemplate($dir, $templatePath, $entityVarSingular, $routePrefix);
    }

    private function generateIndexTemplate(
        string $dir,
        string $templatePath,
        string $entityVarSingular,
        string $entityVarPlural,
        string $entityName,
        string $entityPluralName,
        array  $entityFields,
        bool   $hasTimestamps,
        bool   $hasIsActive,
        string $routePrefix
    ): void
    {
        $tableHeaders = '';
        $tableData = '';
        $processedFields = [];

        foreach ($entityFields as $field => $type) {
            // Evitar duplicados
            if (in_array($field, $processedFields)) {
                continue;
            }
            $processedFields[] = $field;

            $tableHeaders .= "                                <th>" . ucfirst($field) . "</th>\n";

            if (is_array($type) && $type['type'] === 'relation') {
                $targetEntity = $type['relation_class'];
                $shortEntityName = substr($targetEntity, strrpos($targetEntity, '\\') + 1);
                $relatedEntityVarName = lcfirst($shortEntityName);
                $relatedRoutePrefix = strtolower(explode('_', $routePrefix)[0]) . '_' . $relatedEntityVarName;

                $tableData .= "                                <td><a href=\"{{ path('" . $relatedRoutePrefix . "_show', {'id': " . $entityVarSingular . "." . $field . ".id}) }}\" class=\"btn show\" title=\"Ver\" data-action=\"show-entity\">{{ " . $entityVarSingular . "." . $field . ".id }}</a></td>\n";
            } else if ($type === 'bool') {
                $tableData .= "                                <td>{{ " . $entityVarSingular . "." . $field . " ? 'Sí' : 'No' }}</td>\n";
            } elseif ($type === 'object' && str_contains(strtolower($field), 'date')) {
                $tableData .= "                                <td>{{ " . $entityVarSingular . "." . $field . " ? " . $entityVarSingular . "." . $field . "|date('d/m/Y H:i') : '' }}</td>\n";
            } elseif ($type === 'array') {
                $tableData .= "                                <td>{{ " . $entityVarSingular . "." . $field . " is iterable ? " . $entityVarSingular . "." . $field . "|join(', ') : '' }}</td>\n";
            } else {
                $tableData .= "                                <td>{{ " . $entityVarSingular . "." . $field . " }}</td>\n";
            }
        }

        $templateFile = $this->commandDir . '/templates/index.html.twig.tpl';
        if (!file_exists($templateFile)) {
            throw new \RuntimeException(sprintf('Template file "%s" not found.', $templateFile));
        }

        $content = file_get_contents($templateFile);
        $content = str_replace('{{entity_name}}', $entityName, $content);
        $content = str_replace('{{entity_plural_name}}', $entityPluralName, $content);
        $content = str_replace('{{entity_var_singular}}', $entityVarSingular, $content);
        $content = str_replace('{{entity_var_plural}}', $entityVarPlural, $content);
        $content = str_replace('{{route_prefix}}', $routePrefix, $content);
        $content = str_replace('{{template_path}}', $templatePath, $content);
        $content = str_replace('{{table_headers}}', rtrim($tableHeaders), $content);
        $content = str_replace('{{table_data}}', rtrim($tableData), $content);

        // Condicionales para timestamps
        $content = $this->processConditionals($content, 'has_timestamps', $hasTimestamps);

        // Condicionales para isActive
        $content = $this->processConditionals($content, 'has_is_active', $hasIsActive);

        file_put_contents("$dir/index.html.twig", $content);
    }

    private function generateNewTemplate(
        string $dir,
        string $templatePath,
        string $entityVarSingular,
        string $entityName,
        array  $entityFields,
        string $routePrefix
    ): void
    {
        $formFields = '';
        foreach ($entityFields as $field => $type) {
            $formFields .= "                    <div class=\"col-md-6 mb-4 px-1\">\n";
            $formFields .= "                        <div class=\"form-outline\">\n";
            $formFields .= "                            {{ form_widget(form.$field, {\n";
            $formFields .= "                                'attr': {\n";
            $formFields .= "                                    'class': 'form-control',\n";
            $formFields .= "                                    'id': '{$entityVarSingular}_$field'\n";
            $formFields .= "                                }\n";
            $formFields .= "                            }) }}\n";
            $formFields .= "                            {{ form_label(form.$field, null, {\n";
            $formFields .= "                                'label_attr': {\n";
            $formFields .= "                                    'class': 'form-label',\n";
            $formFields .= "                                    'for': '{$entityVarSingular}_$field'\n";
            $formFields .= "                                }\n";
            $formFields .= "                            }) }}\n";
            $formFields .= "                            <div class=\"form-notch\">\n";
            $formFields .= "                                <div class=\"form-notch-leading\"></div>\n";
            $formFields .= "                                <div class=\"form-notch-middle\" style=\"width: 70px;\"></div>\n";
            $formFields .= "                                <div class=\"form-notch-trailing\"></div>\n";
            $formFields .= "                            </div>\n";
            $formFields .= "                        </div>\n";
            $formFields .= "                        <div class=\"invalid-feedback\">\n";
            $formFields .= "                            {{ form_errors(form.$field) }}\n";
            $formFields .= "                        </div>\n";
            $formFields .= "                    </div>\n";
        }

        $templateFile = $this->commandDir . '/templates/_new.html.twig.tpl';
        if (!file_exists($templateFile)) {
            throw new \RuntimeException(sprintf('Template file "%s" not found.', $templateFile));
        }

        $content = file_get_contents($templateFile);
        $content = str_replace('{{entity_name}}', $entityName, $content);
        $content = str_replace('{{entity_var_singular}}', $entityVarSingular, $content);
        $content = str_replace('{{route_prefix}}', $routePrefix, $content);

        // Reemplazar los campos del formulario usando regex y Mustache
        $formFieldsPattern = '/{{#form_fields}}[\s\S]*?{{\/form_fields}}/';
        $content = preg_replace($formFieldsPattern, $formFields, $content);

        file_put_contents("$dir/_new.html.twig", $content);
    }

    private function generateShowTemplate(
        string $dir,
        string $templatePath,
        string $entityVarSingular,
        string $entityName,
        array  $entityFields,
        bool   $hasTimestamps,
        bool   $hasIsActive,
        string $routePrefix  // Añadimos el parámetro routePrefix
    ): void
    {
        $showData = '';
        foreach ($entityFields as $field => $type) {
            $showData .= "                        <tr>\n";
            $showData .= "                            <th class=\"table-dark show-entity-header-cell\">" . ucfirst($field) . "</th>\n";

            if (is_array($type) && $type['type'] === 'relation') {
                $targetEntity = $type['relation_class'];
                $shortEntityName = substr($targetEntity, strrpos($targetEntity, '\\') + 1);
                $relatedEntityVarName = lcfirst($shortEntityName);
                $relatedRoutePrefix = strtolower(explode('_', $routePrefix)[0]) . '_' . $relatedEntityVarName;

                $showData .= "                            <td class=\"show-entity-data-cell\"><a href=\"{{ path('" . $relatedRoutePrefix . "_show', {'id': " . $entityVarSingular . "." . $field . ".id}) }}\" class=\"btn show\" title=\"Ver\" data-action=\"show-entity\">{{ {$entityVarSingular}.{$field}.id }}</a></td>\n";
            } else if ($type === 'bool') {
                $showData .= "                            <td class=\"show-entity-data-cell\">{{ {$entityVarSingular}.{$field} ? 'Sí' : 'No' }}</td>\n";
            } elseif ($type === 'object' && str_contains(strtolower($field), 'date')) {
                $showData .= "                            <td class=\"show-entity-data-cell\">{{ {$entityVarSingular}.{$field} ? {$entityVarSingular}.{$field}|date('d/m/Y H:i:s') : '' }}</td>\n";
            } elseif ($type === 'array') {
                $showData .= "                            <td class=\"show-entity-data-cell\">{{ {$entityVarSingular}.{$field} is iterable ? {$entityVarSingular}.{$field}|join(', ') : '' }}</td>\n";
            } else {
                $showData .= "                            <td class=\"show-entity-data-cell\">{{ {$entityVarSingular}.{$field} }}</td>\n";
            }
            $showData .= "                        </tr>\n";
        }

        $templateFile = $this->commandDir . '/templates/_show.html.twig.tpl';
        if (!file_exists($templateFile)) {
            throw new \RuntimeException(sprintf('Template file "%s" not found.', $templateFile));
        }

        $content = file_get_contents($templateFile);
        $content = str_replace('{{entity_name}}', $entityName, $content);
        $content = str_replace('{{entity_var_singular}}', $entityVarSingular, $content);
        $content = str_replace('{{show_data}}', rtrim($showData), $content);

        // Condicionales para timestamps
        $content = $this->processConditionals($content, 'has_timestamps', $hasTimestamps);

        // Condicionales para isActive
        $content = $this->processConditionals($content, 'has_is_active', $hasIsActive);

        file_put_contents("$dir/_show.html.twig", $content);
    }

    private function generateEditTemplate(
        string $dir,
        string $templatePath,
        string $entityVarSingular,
        string $entityName,
        array  $entityFields,
        string $routePrefix
    ): void
    {
        $formFields = '';
        foreach ($entityFields as $field => $type) {
            $formFields .= "                    <div class=\"col-md-6 mb-4 px-1\">\n";
            $formFields .= "                        <div class=\"form-outline\">\n";
            $formFields .= "                            {{ form_widget(form.$field, {\n";
            $formFields .= "                                'attr': {\n";
            $formFields .= "                                    'class': 'form-control',\n";
            $formFields .= "                                    'id': '{$entityVarSingular}_edit_$field'\n";
            $formFields .= "                                }\n";
            $formFields .= "                            }) }}\n";
            $formFields .= "                            {{ form_label(form.$field, null, {\n";
            $formFields .= "                                'label_attr': {\n";
            $formFields .= "                                    'class': 'form-label',\n";
            $formFields .= "                                    'for': '{$entityVarSingular}_edit_$field'\n";
            $formFields .= "                                }\n";
            $formFields .= "                            }) }}\n";
            $formFields .= "                            <div class=\"form-notch\">\n";
            $formFields .= "                                <div class=\"form-notch-leading\"></div>\n";
            $formFields .= "                                <div class=\"form-notch-middle\" style=\"width: 70px;\"></div>\n";
            $formFields .= "                                <div class=\"form-notch-trailing\"></div>\n";
            $formFields .= "                            </div>\n";
            $formFields .= "                        </div>\n";
            $formFields .= "                        <div class=\"invalid-feedback\">\n";
            $formFields .= "                            {{ form_errors(form.$field) }}\n";
            $formFields .= "                        </div>\n";
            $formFields .= "                    </div>\n";
        }

        $templateFile = $this->commandDir . '/templates/_edit.html.twig.tpl';
        if (!file_exists($templateFile)) {
            throw new \RuntimeException(sprintf('Template file "%s" not found.', $templateFile));
        }

        $content = file_get_contents($templateFile);
        $content = str_replace('{{entity_name}}', $entityName, $content);
        $content = str_replace('{{entity_var_singular}}', $entityVarSingular, $content);
        $content = str_replace('{{route_prefix}}', $routePrefix, $content);

        // Reemplazar los campos del formulario usando regex y Mustache
        $formFieldsPattern = '/{{#form_fields}}[\s\S]*?{{\/form_fields}}/';
        $content = preg_replace($formFieldsPattern, $formFields, $content);

        // Ajustar el título del modal para edición
        $content = str_replace('Crear nuevo {{entity_name}}', 'Editar {{entity_name}}', $content);

        // Ajustar el action del formulario para apuntar a la ruta de edición
        $content = str_replace("'action': path('{{route_prefix}}_new')", "'action': path('{{route_prefix}}_edit', {'id': {{entity_var_singular}}.id})", $content);

        file_put_contents("$dir/_edit.html.twig", $content);
    }

    private function generateDeleteTemplate(
        string $dir,
        string $templatePath,
        string $entityVarSingular,
        string $routePrefix
    ): void
    {
        $templateFile = $this->commandDir . '/templates/_delete.html.twig.tpl';
        if (!file_exists($templateFile)) {
            throw new \RuntimeException(sprintf('Template file "%s" not found.', $templateFile));
        }

        $content = file_get_contents($templateFile);
        $content = str_replace('{{entity_var_singular}}', $entityVarSingular, $content);
        $content = str_replace('{{route_prefix}}', $routePrefix, $content);

        file_put_contents("$dir/_delete.html.twig", $content);
    }
}