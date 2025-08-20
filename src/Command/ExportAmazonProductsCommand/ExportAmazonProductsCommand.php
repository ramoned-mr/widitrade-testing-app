<?php

namespace App\Command\ExportAmazonProductsCommand;

use App\Service\Platform\Amazon\Exporter\AmazonProductExporterInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:export:amazon-products',
    description: 'Exporta productos modificados desde la base de datos hacia un archivo JSON con formato de Amazon'
)]
class ExportAmazonProductsCommand extends Command
{
    private const EXPORT_DIR = 'config/data/amazon/exports';

    public function __construct(
        private AmazonProductExporterInterface $productExporter,
        private string                         $projectDir
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Exportador de Productos Amazon');
        $io->info('Esta acción exportará todos los productos activos de la base de datos');
        $io->info('Los datos modificados desde el panel administrativo serán incluidos');
        $io->newLine();

        try {
            $io->section('Iniciando exportación...');

            // Generar nombre de archivo con timestamp
            $timestamp = date('d-m-Y__H-i-s');
            $fileName = "amazon_{$timestamp}.json";
            $exportPath = $this->getExportPath($fileName);

            $io->info(sprintf('Archivo de salida: %s', $exportPath));

            // Ejecutar la exportación
            $result = $this->productExporter->exportProducts($exportPath);

            // Mostrar resultados
            $this->displayResults($io, $result, $exportPath);

            if ($result->totalExported > 0) {
                $io->success('Exportación completada exitosamente');
                return Command::SUCCESS;
            } else {
                $io->warning('No se exportaron productos. Verifique que existan productos activos en la base de datos.');
                return Command::SUCCESS;
            }

        } catch (\Exception $e) {
            $io->error('Error durante la exportación: ' . $e->getMessage());
            $io->note('Revise los logs para más detalles');
            return Command::FAILURE;
        }
    }

    private function getExportPath(string $fileName): string
    {
        $exportDir = $this->projectDir . '/' . self::EXPORT_DIR;

        // Crear directorio si no existe
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        return $exportDir . '/' . $fileName;
    }

    private function displayResults(SymfonyStyle $io, $result, string $filePath): void
    {
        $io->table(
            ['Métrica', 'Valor'],
            [
                ['Total procesados', $result->totalProcessed],
                ['Exportados exitosamente', $result->totalExported],
                ['Omitidos', $result->skipped],
                ['Fallidos', $result->failed],
                ['Tamaño del archivo', $this->getFileSize($filePath)],
            ]
        );

        // Mostrar errores (si los hay)
        if (!empty($result->errors)) {
            $io->section('Errores encontrados:');

            foreach ($result->errors as $error) {
                $io->writeln(sprintf('• <error>ASIN %s:</error> %s', $error['asin'], $error['error']));
            }
        }

        // Mostrar estadísticas adicionales
        if ($result->totalExported > 0) {
            $io->section('Información adicional:');
            $io->writeln(sprintf('• Productos con imágenes: %d', $result->withImages ?? 0));
            $io->writeln(sprintf('• Productos con precios: %d', $result->withPrices ?? 0));
            $io->writeln(sprintf('• Productos con rankings: %d', $result->withRankings ?? 0));
        }
    }

    private function getFileSize(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return 'N/A';
        }

        $bytes = filesize($filePath);

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}