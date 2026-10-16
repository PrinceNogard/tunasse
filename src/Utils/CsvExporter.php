<?php
declare(strict_types=1);

namespace Tunasse\Utils;

use Tunasse\Finance\TravauxEngine;

class CsvExporter
{
    /**
     * Generate UTF-8 BOM CSV string for Travaux
     */
    public static function generateTravauxCsv(array $appState): string
    {
        $metrics = TravauxEngine::calculate($appState);
        $profiles = $appState['profiles'] ?? [];
        $p1 = $profiles[0]['name'] ?? 'Axel';
        $p2 = $profiles[1]['name'] ?? 'Ariane';

        $lines = [];
        // UTF-8 BOM
        $lines[] = "\xEF\xBB\xBF" . implode(';', [
            Format::sanitizeCsvCell('Date'),
            Format::sanitizeCsvCell('Raison / Facture'),
            Format::sanitizeCsvCell('Lot / Catégorie'),
            Format::sanitizeCsvCell("Avance $p1 (€)"),
            Format::sanitizeCsvCell("Avance $p2 (€)"),
            Format::sanitizeCsvCell('Total Dépense (€)'),
            Format::sanitizeCsvCell('Statut Remboursement'),
            Format::sanitizeCsvCell('Notes')
        ]);

        foreach ($metrics['items'] as $item) {
            $lot = TravauxEngine::resolveItemLot($item, $metrics['lots']);
            $lotName = $lot['name'] ?? 'Non catégorisé';
            $ax = (float)($item['amountAxel'] ?? 0.0);
            $ar = (float)($item['amountAriane'] ?? 0.0);
            $total = $ax + $ar;
            $status = !empty($item['reimbursed']) ? 'Remboursé' : 'Non remboursé';

            $lines[] = implode(';', [
                Format::sanitizeCsvCell(Format::dateFR($item['date'] ?? '')),
                Format::sanitizeCsvCell((string)($item['reason'] ?? '')),
                Format::sanitizeCsvCell($lotName),
                number_format($ax, 2, ',', ''),
                number_format($ar, 2, ',', ''),
                number_format($total, 2, ',', ''),
                Format::sanitizeCsvCell($status),
                Format::sanitizeCsvCell((string)($item['notes'] ?? ''))
            ]);
        }

        // Summary row
        $lines[] = implode(';', [
            Format::sanitizeCsvCell('TOTAL CONSOMME'),
            Format::sanitizeCsvCell(''),
            Format::sanitizeCsvCell(''),
            number_format($metrics['totalAxel'], 2, ',', ''),
            number_format($metrics['totalAriane'], 2, ',', ''),
            number_format($metrics['totalConsumed'], 2, ',', ''),
            Format::sanitizeCsvCell(''),
            Format::sanitizeCsvCell('')
        ]);

        $lines[] = implode(';', [
            Format::sanitizeCsvCell('SOLDE RESTANT ENVELOPPE (50 000 €)'),
            Format::sanitizeCsvCell(''),
            Format::sanitizeCsvCell(''),
            Format::sanitizeCsvCell(''),
            Format::sanitizeCsvCell(''),
            number_format($metrics['remainingBudget'], 2, ',', ''),
            Format::sanitizeCsvCell(''),
            Format::sanitizeCsvCell('')
        ]);

        return implode("\r\n", $lines);
    }
}
