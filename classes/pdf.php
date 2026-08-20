<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace tool_pdfpages;

use setasign\Fpdi\TcpdfFpdi;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/pdflib.php');

/**
 * Class for combining PDFs.
 *
 * @package    tool_pdfpages
 * @author     Trisha Milan <trishamilan@catalyst-au.net>
 * @copyright  2024 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pdf extends TcpdfFpdi {
    /**
     * Combines multiple PDFs into a single PDF.
     *
     * @param array $pdffilepaths An array of file paths to the PDFs that need to be combined.
     * @param string $outputfilepath The path to save the combined PDF.
     * @param bool $printpagenumbers Whether to print page numbers in the footer of the combined PDF (optional,
     *                               defaults to false).
     */
    public function combine_pdfs(array $pdffilepaths, string $outputfilepath, bool $printpagenumbers = false): void {
        $pdf = new pdf();

        $this->initialise_pdf_settings($pdf);

        $totalpages = 0;
        foreach ($pdffilepaths as $pdffilepath) {
            $totalpages = $this->add_pages_to_pdf($pdf, $pdffilepath, $totalpages, $printpagenumbers);
        }

        $pdf->Output($outputfilepath, 'F');
    }

    /**
     * Initialise PDF settings for the TcpdfFpdi object.
     *
     * @param  TcpdfFpdi $pdf The PDF object to initialise.
     */
    protected function initialise_pdf_settings(TcpdfFpdi $pdf): void {
        $pdf->setPageUnit('pt');
        $pdf->SetFillColor(255, 255, 176);
        $pdf->SetDrawColor(0, 0, 0);
        // One pixel at 100 DPI is 0.72 points.
        $pdf->SetLineWidth(0.72);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
    }

    /**
     * Add pages from the given PDF file to the combined PDF.
     *
     * @param TcpdfFpdi $pdf The combined PDF object.
     * @param string $pdffilepath The file path of the PDF to import.
     * @param int $totalpages The current total number of pages.
     * @param bool $printpagenumbers Whether to print page numbers in the footer of the combined PDF (optional,
     *                               defaults to false).
     * @return int The updated total number of pages.
     */
    protected function add_pages_to_pdf(
        TcpdfFpdi $pdf,
        string $pdffilepath,
        int $totalpages,
        bool $printpagenumbers = false
    ): int {
        $pagecount = $pdf->setSourceFile($pdffilepath);
        for ($pageno = 1; $pageno <= $pagecount; $pageno++) {
            $template = $pdf->importPage($pageno);
            $size = $pdf->getTemplateSize($template);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->setPageOrientation($size['orientation'], false, 0);
            $pdf->useTemplate($template);

            if ($printpagenumbers) {
                $pdf->SetX(-5);
                $pdf->SetY(-32);
                $pdf->SetRightMargin(-23);
                $pdf->SetFont('helvetica', 'R', 12);
                $pdf->Cell(0, 10, 'Page ' . $pdf->getAliasNumPage() . ' of ' . $pdf->getAliasNbPages(), 0, 0, 'R');
            }
        }

        return $totalpages += $pagecount;
    }
}
