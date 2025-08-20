<?php

namespace App\Command\ImportAmazonProductsCommand;

use App\Service\Platform\Amazon\Importer\AmazonProductImporterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

#[AsCommand(
    name: 'app:import:amazon-products',
    description: 'Importa productos desde el archivo JSON de Amazon ubicado en config/data/amazon/amazon.json'
)]
class ImportAmazonProductsCommand extends Command
{
    private const DEFAULT_JSON_PATH = 'config/data/amazon/amazon.json';

    public function __construct(
        private AmazonProductImporterInterface $productImporter,
        private string                         $projectDir
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Importador de Productos Amazon');
        $io->warning('ADVERTENCIA IMPORTANTE:');
        $io->writeln('• Esta acción importará datos desde el archivo JSON');
        $io->writeln('• Si ya ha modificado productos desde el panel administrativo');
        $io->writeln('• Esta acción SOBREESCRIBIRÁ sus cambios de forma definitiva');
        $io->newLine();

        // Pregunta de confirmación
        $question = new ConfirmationQuestion(
            '<question>¿Está seguro de importar los datos? (yes/no): </question>',
            false,
            '/^yes$/i'  //cualquier valor distinto de "yes" es considerado "no".
        );

        $helper = $this->getHelper('question');

        if (!$helper->ask($input, $output, $question)) {
            $io->warning('Importación cancelada por el usuario');
            return Command::SUCCESS;
        }

        $filePath = $this->getDefaultFilePath();

        $io->info(sprintf('Archivo: %s', $filePath));

        // Validar que el archivo existe
        if (!file_exists($filePath)) {
            $io->error(sprintf('El archivo "%s" no existe', $filePath));
            $io->note('Por favor, coloque el archivo JSON en: config/data/amazon/amazon.json');
            return Command::FAILURE;
        }

        // Leer el contenido del archivo
        $jsonContent = file_get_contents($filePath);
        if (false === $jsonContent) {
            $io->error(sprintf('No se pudo leer el archivo "%s"', $filePath));
            return Command::FAILURE;
        }

        try {
            $io->section('Iniciando importación...');

            // Ejecutar la importación
            $result = $this->productImporter->importProducts($jsonContent, true, null);

            // Mostrar resultados
            $this->displayResults($io, $result);

            $io->success('Importación completada exitosamente');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Error durante la importación: ' . $e->getMessage());
            $io->note('Revise los logs para más detalles');
            return Command::FAILURE;
        }
    }

    private function getDefaultFilePath(): string
    {
        return $this->projectDir . '/' . self::DEFAULT_JSON_PATH;
    }

    private function displayResults(SymfonyStyle $io, $result): void
    {
        $io->table(
            ['Métrica', 'Valor'],
            [
                ['Total procesados', $result->totalProcessed],
                ['Importados nuevos', $result->successfullyImported],
                ['Actualizados', $result->updated],
                ['Omitidos', $result->skipped],
                ['Fallidos', $result->failed],
            ]
        );

        // Mostrar errores (si los hay)
        if (!empty($result->errors)) {
            $io->section('Errores encontrados:');

            foreach ($result->errors as $error) {
                $io->writeln(sprintf('• <error>ASIN %s:</error> %s', $error['asin'], $error['error']));
            }
        }
    }
}