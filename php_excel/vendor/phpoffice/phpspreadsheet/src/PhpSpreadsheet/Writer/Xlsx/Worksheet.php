<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use Composer\Pcre\Preg;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ErrorValue;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx\Namespaces;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Shared\XMLWriter;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\ConditionalColorScale;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\ConditionalDataBar;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\ConditionalFormattingRuleExtension;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\RowDimension;
use PhpOffice\PhpSpreadsheet\Worksheet\SheetView;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet as PhpspreadsheetWorksheet;

class Worksheet extends WriterPart
{
    private string $numberStoredAsText = '';

    private string $formula = '';

    private string $formulaRange = '';

    private string $twoDigitTextYear = '';

    private string $evalError = '';

    private bool $explicitStyle0;

    private bool $useDynamicArrays = false;

    /**
     * Write worksheet to XML format.
     *
     * @param string[] $stringTable
     * @param bool $includeCharts Flag indicating if we should write charts
     *
     * @return string XML Output
     */
    public function writeWorksheet(PhpspreadsheetWorksheet $worksheet, array $stringTable = [], bool $includeCharts = false): string
    {
        $this->useDynamicArrays = $this->getParentWriter()->useDynamicArrays();
        $this->explicitStyle0 = $this->getParentWriter()->getExplicitStyle0();
        $worksheet->calculateArrays($this->getParentWriter()->getPreCalculateFormulas());
        $this->numberStoredAsText = '';
        $this->formula = '';
        $this->formulaRange = '';
        $this->twoDigitTextYear = '';
        $this->evalError = '';
        // Create XML writer
        $objWriter = null;
        if ($this->getParentWriter()->getUseDiskCaching()) {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_DISK, $this->getParentWriter()->getDiskCachingDirectory());
        } else {
            $objWriter = new XMLWriter(XMLWriter::STORAGE_MEMORY);
        }

        // XML header
        $objWriter->startDocument('1.0', 'UTF-8', 'yes');

        // Worksheet
        $objWriter->startElement('worksheet');
        $objWriter->writeAttribute('xml:space', 'preserve');
        $objWriter->writeAttribute('xmlns', Namespaces::MAIN);
        $objWriter->writeAttribute('xmlns:r', Namespaces::SCHEMA_OFFICE_DOCUMENT);

        $objWriter->writeAttribute('xmlns:xdr', Namespaces::SPREADSHEET_DRAWING);
        $objWriter->writeAttribute('xmlns:x14', Namespaces::DATA_VALIDATIONS1);
        $objWriter->writeAttribute('xmlns:xm', Namespaces::DATA_VALIDATIONS2);
        $objWriter->writeAttribute('xmlns:mc', Namespaces::COMPATIBILITY);
        $objWriter->writeAttribute('mc:Ignorable', 'x14ac');
        $objWriter->writeAttribute('xmlns:x14ac', Namespaces::SPREADSHEETML_AC);

        // sheetPr
        $this->writeSheetPr($objWriter, $worksheet);

        // Dimension
        $this->writeDimension($objWriter, $worksheet);

        // sheetViews
        $this->writeSheetViews($objWriter, $worksheet);

        // sheetFormatPr
        $this->writeSheetFormatPr($objWriter, $worksheet);

        // cols
        $this->writeCols($objWriter, $worksheet);

        // sheetData
        $this->writeSheetData($objWriter, $worksheet, $stringTable);

        // sheetProtection
        $this->writeSheetProtection($objWriter, $worksheet);

        // protectedRanges
        $this->writeProtectedRanges($objWriter, $worksheet);

        // autoFilter
        $this->writeAutoFilter($objWriter, $worksheet);

        // mergeCells
        $this->writeMergeCells($objWriter, $worksheet);

        // conditionalFormatting
        $this->writeConditionalFormatting($objWriter, $worksheet);

        // dataValidations
        $this->writeDataValidations($objWriter, $worksheet);

        // hyperlinks
        $this->writeHyperlinks($objWriter, $worksheet);

        // Print options
        $this->writePrintOptions($objWriter, $worksheet);

        // Page margins
        $this->writePageMargins($objWriter, $worksheet);

        // Page setup
        $this->writePageSetup($objWriter, $worksheet);

        // Header / footer
        $this->writeHeaderFooter($objWriter, $worksheet);

        // Breaks
        $this->writeBreaks($objWriter, $worksheet);

        // IgnoredErrors
        $this->writeIgnoredErrors($objWriter);

        // Drawings and/or Charts
        $this->writeDrawings($objWriter, $worksheet, $includeCharts);

        // LegacyDrawing
        $this->writeLegacyDrawing($objWriter, $worksheet);

        // LegacyDrawingHF
        $this->writeLegacyDrawingHF($objWriter, $worksheet);

        // AlternateContent
        $this->writeAlternateContent($objWriter, $worksheet);

        // BackgroundImage must come after ignored, before table
        $this->writeBackgroundImage($objWriter, $worksheet);

        // Table
        $this->writeTable($objWriter, $worksheet);

        // ConditionalFormattingRuleExtensionList
        // (Must be inserted last. Not insert last, an Excel parse error will occur)
        $this->writeExtLst($objWriter, $worksheet);

        $objWriter->endElement();

        // Return
        return $objWriter->getData();
    }

    private function writeIgnoredError(XMLWriter $objWriter, bool &$started, string $attr, string $cells): void
    {
        if ($cells !== '') {
            if (!$started) {
                $objWriter->startElement('ignoredErrors');
                $started = true;
            }
            $objWriter->startElement('ignoredError');
            $objWriter->writeAttribute('sqref', substr($cells, 1));
            $objWriter->writeAttribute($attr, '1');
            $objWriter->endElement();
        }
    }

    private function writeIgnoredErrors(XMLWriter $objWriter): void
    {
        $started = false;
        $this->writeIgnoredError($objWriter, $started, 'numberStoredAsText', $this->numberStoredAsText);
        $this->writeIgnoredError($objWriter, $started, 'formula', $this->formula);
        $this->writeIgnoredError($objWriter, $started, 'formulaRange', $this->formulaRange);
        $this->writeIgnoredError($objWriter, $started, 'twoDigitTextYear', $this->twoDigitTextYear);
        $this->writeIgnoredError($objWriter, $started, 'evalError', $this->evalError);
        if ($started) {
            $objWriter->endElement();
        }
    }

    /**
     * Write SheetPr.
     */
    private function writeSheetPr(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        // sheetPr
        $objWriter->startElement('sheetPr');
        if ($worksheet->getParentOrThrow()->hasMacros()) {
            //if the workbook have macros, we need to have codeName for the sheet
            if (!$worksheet->hasCodeName()) {
                $worksheet->setCodeName($worksheet->getTitle());
            }
            self::writeAttributeNotNull($objWriter, 'codeName', $worksheet->getCodeName());
        }
        $autoFilterRange = $worksheet->getAutoFilter()->getRange();
        if (!empty($autoFilterRange)) {
            $objWriter->writeAttribute('filterMode', '1');
            if (!$worksheet->getAutoFilter()->getEvaluated()) {
                $worksheet->getAutoFilter()->showHideRows();
            }
        }
        $tables = $worksheet->getTableCollection();
        if (count($tables)) {
            foreach ($tables as $table) {
                if (!$table->getAutoFilter()->getEvaluated()) {
                    $table->getAutoFilter()->showHideRows();
                }
            }
        }

        // tabColor
        if ($worksheet->isTabColorSet()) {
            $objWriter->startElement('tabColor');
            $objWriter->writeAttribute('rgb', $worksheet->getTabColor()->getARGB() ?? '');
            $objWriter->endElement();
        }

        // outlinePr
        $objWriter->startElement('outlinePr');
        $objWriter->writeAttribute('summaryBelow', ($worksheet->getShowSummaryBelow() ? '1' : '0'));
        $objWriter->writeAttribute('summaryRight', ($worksheet->getShowSummaryRight() ? '1' : '0'));
        $objWriter->endElement();

        // pageSetUpPr
        if ($worksheet->getPageSetup()->getFitToPage()) {
            $objWriter->startElement('pageSetUpPr');
            $objWriter->writeAttribute('fitToPage', '1');
            $objWriter->endElement();
        }

        $objWriter->endElement();
    }

    /**
     * Write Dimension.
     */
    private function writeDimension(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        // dimension
        $objWriter->startElement('dimension');
        $objWriter->writeAttribute('ref', $worksheet->calculateWorksheetDimension());
        $objWriter->endElement();
    }

    /**
     * Write SheetViews.
     */
    private function writeSheetViews(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        // sheetViews
        $objWriter->startElement('sheetViews');

        // Sheet selected?
        $sheetSelected = false;
        if ($this->getParentWriter()->getSpreadsheet()->getIndex($worksheet) == $this->getParentWriter()->getSpreadsheet()->getActiveSheetIndex()) {
            $sheetSelected = true;
        }

        // sheetView
        $objWriter->startElement('sheetView');
        $objWriter->writeAttribute('tabSelected', $sheetSelected ? '1' : '0');
        $objWriter->writeAttribute('workbookViewId', '0');

        // Zoom scales
        $zoomScale = $worksheet->getSheetView()->getZoomScale();
        if ($zoomScale !== 100 && $zoomScale !== null) {
            $objWriter->writeAttribute('zoomScale', (string) $zoomScale);
        }
        $zoomScale = $worksheet->getSheetView()->getZoomScaleNormal();
        if ($zoomScale !== 100 && $zoomScale !== null) {
            $objWriter->writeAttribute('zoomScaleNormal', (string) $zoomScale);
        }
        $zoomScale = $worksheet->getSheetView()->getZoomScalePageLayoutView();
        if ($zoomScale !== 100) {
            $objWriter->writeAttribute('zoomScalePageLayoutView', (string) $zoomScale);
        }
        $zoomScale = $worksheet->getSheetView()->getZoomScaleSheetLayoutView();
        if ($zoomScale !== 100) {
            $objWriter->writeAttribute('zoomScaleSheetLayoutView', (string) $zoomScale);
        }

        // Show zeros (Excel also writes this attribute only if set to false)
        if ($worksheet->getSheetView()->getShowZeros() === false) {
            $objWriter->writeAttribute('showZeros', '0');
        }

        // View Layout Type
        if ($worksheet->getSheetView()->getView() !== SheetView::SHEETVIEW_NORMAL) {
            $objWriter->writeAttribute('view', $worksheet->getSheetView()->getView());
        }

        // Gridlines
        if ($worksheet->getShowGridlines()) {
            $objWriter->writeAttribute('showGridLines', 'true');
        } else {
            $objWriter->writeAttribute('showGridLines', 'false');
        }

        // Row and column headers
        if ($worksheet->getShowRowColHeaders()) {
            $objWriter->writeAttribute('showRowColHeaders', '1');
        } else {
            $objWriter->writeAttribute('showRowColHeaders', '0');
        }

        // Right-to-left
        if ($worksheet->getRightToLeft()) {
            $objWriter->writeAttribute('rightToLeft', 'true');
        }

        $topLeftCell = $worksheet->getTopLeftCell();
        if (!empty($topLeftCell) && $worksheet->getPaneState() !== PhpspreadsheetWorksheet::PANE_FROZEN && $worksheet->getPaneState() !== PhpspreadsheetWorksheet::PANE_FROZENSPLIT) {
            $objWriter->writeAttribute('topLeftCell', $topLeftCell);
        }
        $activeCell = $worksheet->getActiveCell();
        $sqref = $worksheet->getSelectedCells();

        // Pane
        if ($worksheet->usesPanes()) {
            $objWriter->startElement('pane');
            $xSplit = $worksheet->getXSplit();
            $ySplit = $worksheet->getYSplit();
            $pane = $worksheet->getActivePane();
            $paneTopLeftCell = $worksheet->getPaneTopLeftCell();
            $paneState = $worksheet->getPaneState();
            $normalFreeze = '';
            if ($paneState === PhpspreadsheetWorksheet::PANE_FROZEN) {
                if ($ySplit > 0) {
                    $normalFreeze = ($xSplit <= 0) ? 'bottomLeft' : 'bottomRight';
                } else {
                    $normalFreeze = 'topRight';
                }
            }
            if ($xSplit > 0) {
                $objWriter->writeAttribute('xSplit', "$xSplit");
            }
            if ($ySplit > 0) {
                $objWriter->writeAttribute('ySplit', "$ySplit");
            }
            if ($normalFreeze !== '') {
                $objWriter->writeAttribute('activePane', $normalFreeze);
            } elseif ($pane !== '') {
                $objWriter->writeAttribute('activePane', $pane);
            }
            if ($paneState !== '') {
                $objWriter->writeAttribute('state', $paneState);
            }
            if ($paneTopLeftCell !== '') {
                $objWriter->writeAttribute('topLeftCell', $paneTopLeftCell);
            }
            $objWriter->endElement(); // pane

            if ($normalFreeze !== '') {
                $objWriter->startElement('selection');
                $objWriter->writeAttribute('pane', $normalFreeze);
                if ($activeCell !== '') {
                    $objWriter->writeAttribute('activeCell', $activeCell);
                }
                if ($sqref !== '') {
                    $objWriter->writeAttribute('sqref', $sqref);
                }
                $objWriter->endElement(); // selection
                $sqref = $activeCell = '';
            } else {
                foreach ($worksheet->getPanes() as $panex) {
                    if ($panex !== null) {
                        $sqref = $activeCell = '';
                        $objWriter->startElement('selection');
                        $objWriter->writeAttribute('pane', $panex->getPosition());
                        $activeCellPane = $panex->getActiveCell();
                        if ($activeCellPane !== '') {
                            $objWriter->writeAttribute('activeCell', $activeCellPane);
                        }
                        $sqrefPane = $panex->getSqref();
                        if ($sqrefPane !== '') {
                            $objWriter->writeAttribute('sqref', $sqrefPane);
                        }
                        $objWriter->endElement(); // selection
                    }
                }
            }
        }

        // Selection
        // Only need to write selection element if we have a split pane
        // We cheat a little by over-riding the active cell selection, setting it to the split cell
        if (!empty($sqref) || !empty($activeCell)) {
            $objWriter->startElement('selection');
            if (!empty($activeCell)) {
                $objWriter->writeAttribute('activeCell', $activeCell);
            }
            if (!empty($sqref)) {
                $objWriter->writeAttribute('sqref', $sqref);
            }
            $objWriter->endElement(); // selection
        }

        $objWriter->endElement();

        $objWriter->endElement();
    }

    /**
     * Write SheetFormatPr.
     */
    private function writeSheetFormatPr(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        // sheetFormatPr
        $objWriter->startElement('sheetFormatPr');

        // Default row height
        if ($worksheet->getDefaultRowDimension()->getRowHeight() >= 0) {
            $objWriter->writeAttribute('customHeight', 'true');
            $objWriter->writeAttribute('defaultRowHeight', StringHelper::formatNumber($worksheet->getDefaultRowDimension()->getRowHeight()));
        } else {
            $objWriter->writeAttribute('defaultRowHeight', '14.4');
        }

        // Set Zero Height row
        if ($worksheet->getDefaultRowDimension()->getZeroHeight()) {
            $objWriter->writeAttribute('zeroHeight', '1');
        }

        // Default column width
        if ($worksheet->getDefaultColumnDimension()->getWidth() >= 0) {
            $objWriter->writeAttribute('defaultColWidth', StringHelper::formatNumber($worksheet->getDefaultColumnDimension()->getWidth()));
        }

        // Outline level - row
        $outlineLevelRow = 0;
        foreach ($worksheet->getRowDimensions() as $dimension) {
            if ($dimension->getOutlineLevel() > $outlineLevelRow) {
                $outlineLevelRow = $dimension->getOutlineLevel();
            }
        }
        $objWriter->writeAttribute('outlineLevelRow', (string) (int) $outlineLevelRow);

        // Outline level - column
        $outlineLevelCol = 0;
        foreach ($worksheet->getColumnDimensions() as $dimension) {
            if ($dimension->getOutlineLevel() > $outlineLevelCol) {
                $outlineLevelCol = $dimension->getOutlineLevel();
            }
        }
        $objWriter->writeAttribute('outlineLevelCol', (string) (int) $outlineLevelCol);

        $objWriter->endElement();
    }

    /**
     * Write Cols.
     */
    private function writeCols(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        // cols
        if (count($worksheet->getColumnDimensions()) > 0) {
            $objWriter->startElement('cols');

            $worksheet->calculateColumnWidths();

            // Loop through column dimensions
            foreach ($worksheet->getColumnDimensions() as $colDimension) {
                // col
                $objWriter->startElement('col');
                $objWriter->writeAttribute('min', (string) Coordinate::columnIndexFromString($colDimension->getColumnIndex()));
                $objWriter->writeAttribute('max', (string) Coordinate::columnIndexFromString($colDimension->getColumnIndex()));

                if ($colDimension->getWidth() < 0) {
                    // No width set, apply default of 10
                    $objWriter->writeAttribute('width', '9.10');
                } else {
                    // Width set
                    $objWriter->writeAttribute('width', StringHelper::formatNumber($colDimension->getWidth()));
                }

                // Column visibility
                if ($colDimension->getVisible() === false) {
                    $objWriter->writeAttribute('hidden', 'true');
                }

                // Auto size?
                if ($colDimension->getAutoSize()) {
                    $objWriter->writeAttribute('bestFit', 'true');
                }

                // Custom width?
                if ($colDimension->getWidth() != $worksheet->getDefaultColumnDimension()->getWidth()) {
                    $objWriter->writeAttribute('customWidth', 'true');
                }

                // Collapsed
                if ($colDimension->getCollapsed() === true) {
                    $objWriter->writeAttribute('collapsed', 'true');
                }

                // Outline level
                if ($colDimension->getOutlineLevel() > 0) {
                    $objWriter->writeAttribute('outlineLevel', (string) $colDimension->getOutlineLevel());
                }

                // Style
                $objWriter->writeAttribute('style', (string) $colDimension->getXfIndex());

                $objWriter->endElement();
            }

            $objWriter->endElement();
        }
    }

    /**
     * Write SheetProtection.
     */
    private function writeSheetProtection(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        $protection = $worksheet->getProtection();
        if (!$protection->isProtectionEnabled()) {
            return;
        }
        // sheetProtection
        $objWriter->startElement('sheetProtection');

        if ($protection->getAlgorithm()) {
            $objWriter->writeAttribute('algorithmName', $protection->getAlgorithm());
            $objWriter->writeAttribute('hashValue', $protection->getPassword());
            $objWriter->writeAttribute('saltValue', $protection->getSalt());
            $objWriter->writeAttribute('spinCount', (string) $protection->getSpinCount());
        } elseif ($protection->getPassword() !== '') {
            $objWriter->writeAttribute('password', $protection->getPassword());
        }

        self::writeProtectionAttribute($objWriter, 'sheet', $protection->getSheet());
        self::writeProtectionAttribute($objWriter, 'objects', $protection->getObjects());
        self::writeProtectionAttribute($objWriter, 'scenarios', $protection->getScenarios());
        self::writeProtectionAttribute($objWriter, 'formatCells', $protection->getFormatCells());
        self::writeProtectionAttribute($objWriter, 'formatColumns', $protection->getFormatColumns());
        self::writeProtectionAttribute($objWriter, 'formatRows', $protection->getFormatRows());
        self::writeProtectionAttribute($objWriter, 'insertColumns', $protection->getInsertColumns());
        self::writeProtectionAttribute($objWriter, 'insertRows', $protection->getInsertRows());
        self::writeProtectionAttribute($objWriter, 'insertHyperlinks', $protection->getInsertHyperlinks());
        self::writeProtectionAttribute($objWriter, 'deleteColumns', $protection->getDeleteColumns());
        self::writeProtectionAttribute($objWriter, 'deleteRows', $protection->getDeleteRows());
        self::writeProtectionAttribute($objWriter, 'sort', $protection->getSort());
        self::writeProtectionAttribute($objWriter, 'autoFilter', $protection->getAutoFilter());
        self::writeProtectionAttribute($objWriter, 'pivotTables', $protection->getPivotTables());
        self::writeProtectionAttribute($objWriter, 'selectLockedCells', $protection->getSelectLockedCells());
        self::writeProtectionAttribute($objWriter, 'selectUnlockedCells', $protection->getSelectUnlockedCells());
        $objWriter->endElement();
    }

    private static function writeProtectionAttribute(XMLWriter $objWriter, string $name, ?bool $value): void
    {
        if ($value === true) {
            $objWriter->writeAttribute($name, '1');
        } elseif ($value === false) {
            $objWriter->writeAttribute($name, '0');
        }
    }

    private static function writeAttributeIf(XMLWriter $objWriter, ?bool $condition, string $attr, string $val): void
    {
        if ($condition) {
            $objWriter->writeAttribute($attr, $val);
        }
    }

    private static function writeAttributeNotNull(XMLWriter $objWriter, string $attr, ?string $val): void
    {
        if ($val !== null) {
            $objWriter->writeAttribute($attr, $val);
        }
    }

    private static function writeElementIf(XMLWriter $objWriter, bool $condition, string $attr, string $val): void
    {
        if ($condition) {
            $objWriter->writeElement($attr, $val);
        }
    }

    private static function writeOtherCondElements(XMLWriter $objWriter, Conditional $conditional, string $cellCoordinate): void
    {
        $conditions = $conditional->getConditions();
        if (
            $conditional->getConditionType() == Conditional::CONDITION_CELLIS
            || $conditional->getConditionType() == Conditional::CONDITION_EXPRESSION
            || !empty($conditions)
        ) {
            foreach ($conditions as $formula) {
                // Formula
                if (is_bool($formula)) {
                    $formula = $formula ? 'TRUE' : 'FALSE';
                }
                $objWriter->writeElement('formula', FunctionPrefix::addFunctionPrefix("$formula"));
            }
        } else {
            if ($conditional->getConditionType() == Conditional::CONDITION_CONTAINSBLANKS) {
                // formula copied from ms xlsx xml source file
                $objWriter->writeElement('formula', 'LEN(TRIM(' . $cellCoordinate . '))=0');
            } elseif ($conditional->getConditionType() == Conditional::CONDITION_NOTCONTAINSBLANKS) {
                // formula copied from ms xlsx xml source file
                $objWriter->writeElement('formula', 'LEN(TRIM(' . $cellCoordinate . '))>0');
            } elseif ($conditional->getConditionType() == Conditional::CONDITION_CONTAINSERRORS) {
                // formula copied from ms xlsx xml source file
                $objWriter->writeElement('formula', 'ISERROR(' . $cellCoordinate . ')');
            } elseif ($conditional->getConditionType() == Conditional::CONDITION_NOTCONTAINSERRORS) {
                // formula copied from ms xlsx xml source file
                $objWriter->writeElement('formula', 'NOT(ISERROR(' . $cellCoordinate . '))');
            }
        }
    }

    private static function writeTimePeriodCondElements(XMLWriter $objWriter, Conditional $conditional, string $cellCoordinate): void
    {
        $txt = $conditional->getText();
        if (!empty($txt)) {
            $objWriter->writeAttribute('timePeriod', $txt);
            if (empty($conditional->getConditions())) {
                if ($conditional->getOperatorType() == Conditional::TIMEPERIOD_TODAY) {
                    $objWriter->writeElement('formula', 'FLOOR(' . $cellCoordinate . ')=TODAY()');
                } elseif ($conditional->getOperatorType() == Conditional::TIMEPERIOD_TOMORROW) {
                    $objWriter->writeElement('formula', 'FLOOR(' . $cellCoordinate . ')=TODAY()+1');
                } elseif ($conditional->getOperatorType() == Conditional::TIMEPERIOD_YESTERDAY) {
                    $objWriter->writeElement('formula', 'FLOOR(' . $cellCoordinate . ')=TODAY()-1');
                } elseif ($conditional->getOperatorType() == Conditional::TIMEPERIOD_LAST_7_DAYS) {
                    $objWriter->writeElement('formula', 'AND(TODAY()-FLOOR(' . $cellCoordinate . ',1)<=6,FLOOR(' . $cellCoordinate . ',1)<=TODAY())');
                } elseif ($conditional->getOperatorType() == Conditional::TIMEPERIOD_LAST_WEEK) {
                    $objWriter->writeElement('formula', 'AND(TODAY()-ROUNDDOWN(' . $cellCoordinate . ',0)>=(WEEKDAY(TODAY())),TODAY()-ROUNDDOWN(' . $cellCoordinate . ',0)<(WEEKDAY(TODAY())+7))');
                } elseif ($conditional->getOperatorType() == Conditional::TIMEPERIOD_THIS_WEEK) {
                    $objWriter->writeElement('formula', 'AND(TODAY()-ROUNDDOWN(' . $cellCoordinate . ',0)<=WEEKDAY(TODAY())-1,ROUNDDOWN(' . $cellCoordinate . ',0)-TODAY()<=7-WEEKDAY(TODAY()))');
                } elseif ($conditional->getOperatorType() == Conditional::TIMEPERIOD_NEXT_WEEK) {
                    $objWriter->writeElement('formula', 'AND(ROUNDDOWN(' . $cellCoordinate . ',0)-TODAY()>(7-WEEKDAY(TODAY())),ROUNDDOWN(' . $cellCoordinate . ',0)-TODAY()<(15-WEEKDAY(TODAY())))');
                } elseif ($conditional->getOperatorType() == Conditional::TIMEPERIOD_LAST_MONTH) {
                    $objWriter->writeElement('formula', 'AND(MONTH(' . $cellCoordinate . ')=MONTH(EDATE(TODAY(),0-1)),YEAR(' . $cellCoordinate . ')=YEAR(EDATE(TODAY(),0-1)))');
                } elseif ($conditional->getOperatorType() == Conditional::TIMEPERIOD_THIS_MONTH) {
                    $objWriter->writeElement('formula', 'AND(MONTH(' . $cellCoordinate . ')=MONTH(TODAY()),YEAR(' . $cellCoordinate . ')=YEAR(TODAY()))');
                } elseif ($conditional->getOperatorType() == Conditional::TIMEPERIOD_NEXT_MONTH) {
                    $objWriter->writeElement('formula', 'AND(MONTH(' . $cellCoordinate . ')=MONTH(EDATE(TODAY(),0+1)),YEAR(' . $cellCoordinate . ')=YEAR(EDATE(TODAY(),0+1)))');
                }
            } else {
                $objWriter->writeElement('formula', (string) ($conditional->getConditions()[0]));
            }
        }
    }

    private static function writeTextCondElements(XMLWriter $objWriter, Conditional $conditional, string $cellCoordinate): void
    {
        $txt = $conditional->getText();
        if (!empty($txt)) {
            $objWriter->writeAttribute('text', $txt);
            if (empty($conditional->getConditions())) {
                if ($conditional->getOperatorType() == Conditional::OPERATOR_CONTAINSTEXT) {
                    $objWriter->writeElement('formula', 'NOT(ISERROR(SEARCH("' . $txt . '",' . $cellCoordinate . ')))');
                } elseif ($conditional->getOperatorType() == Conditional::OPERATOR_BEGINSWITH) {
                    $objWriter->writeElement('formula', 'LEFT(' . $cellCoordinate . ',LEN("' . $txt . '"))="' . $txt . '"');
                } elseif ($conditional->getOperatorType() == Conditional::OPERATOR_ENDSWITH) {
                    $objWriter->writeElement('formula', 'RIGHT(' . $cellCoordinate . ',LEN("' . $txt . '"))="' . $txt . '"');
                } elseif ($conditional->getOperatorType() == Conditional::OPERATOR_NOTCONTAINS) {
                    $objWriter->writeElement('formula', 'ISERROR(SEARCH("' . $txt . '",' . $cellCoordinate . '))');
                }
            } else {
                $objWriter->writeElement('formula', (string) ($conditional->getConditions()[0]));
            }
        }
    }

    private static function writeExtConditionalFormattingElements(XMLWriter $objWriter, ConditionalFormattingRuleExtension $ruleExtension): void
    {
        $prefix = 'x14';
        $objWriter->startElementNs($prefix, 'conditionalFormatting', null);

        $objWriter->startElementNs($prefix, 'cfRule', null);
        $objWriter->writeAttribute('type', $ruleExtension->getCfRule());
        $objWriter->writeAttribute('id', $ruleExtension->getId());
        $objWriter->startElementNs($prefix, 'dataBar', null);
        $dataBar = $ruleExtension->getDataBarExt();
        foreach ($dataBar->getXmlAttributes() as $attrKey => $val) {
            /** @var string $val */
            $objWriter->writeAttribute($attrKey, $val);
        }
        $minCfvo = $dataBar->getMinimumConditionalFormatValueObject();
        // Phpstan is wrong about the next statement.
        if ($minCfvo !== null) { // @phpstan-ignore-line
            $objWriter->startElementNs($prefix, 'cfvo', null);
            $objWriter->writeAttribute('type', $minCfvo->getType());
            if ($minCfvo->getCellFormula()) {
                $objWriter->writeElement('xm:f', $minCfvo->getCellFormula());
            }
            $objWriter->endElement(); //end cfvo
        }

        $maxCfvo = $dataBar->getMaximumConditionalFormatValueObject();
        // Phpstan is wrong about the next statement.
        if ($maxCfvo !== null) { // @phpstan-ignore-line
            $objWriter->startElementNs($prefix, 'cfvo', null);
            $objWriter->writeAttribute('type', $maxCfvo->getType());
            if ($maxCfvo->getCellFormula()) {
                $objWriter->writeElement('xm:f', $maxCfvo->getCellFormula());
            }
            $objWriter->endElement(); //end cfvo
        }

        foreach ($dataBar->getXmlElements() as $elmKey => $elmAttr) {
            /** @var string[] $elmAttr */
            $objWriter->startElementNs($prefix, $elmKey, null);
            foreach ($elmAttr as $attrKey => $attrVal) {
                $objWriter->writeAttribute($attrKey, $attrVal);
            }
            $objWriter->endElement(); //end elmKey
        }
        $objWriter->endElement(); //end dataBar
        $objWriter->endElement(); //end cfRule
        $objWriter->writeElement('xm:sqref', $ruleExtension->getSqref());
        $objWriter->endElement(); //end conditionalFormatting
    }

    private static function writeDataBarElements(XMLWriter $objWriter, ?ConditionalDataBar $dataBar): void
    {
        if ($dataBar) {
            $objWriter->startElement('dataBar');
            self::writeAttributeIf($objWriter, null !== $dataBar->getShowValue(), 'showValue', $dataBar->getShowValue() ? '1' : '0');

            $minCfvo = $dataBar->getMinimumConditionalFormatValueObject();
            if ($minCfvo) {
                $objWriter->startElement('cfvo');
                $objWriter->writeAttribute('type', $minCfvo->getType());
                self::writeAttributeIf($objWriter, $minCfvo->getValue() !== null, 'val', (string) $minCfvo->getValue());
                $objWriter->endElement();
            }
            $maxCfvo = $dataBar->getMaximumConditionalFormatValueObject();
            if ($maxCfvo) {
                $objWriter->startElement('cfvo');
                $objWriter->writeAttribute('type', $maxCfvo->getType());
                self::writeAttributeIf($objWriter, $maxCfvo->getValue() !== null, 'val', (string) $maxCfvo->getValue());
                $objWriter->endElement();
            }
            if ($dataBar->getColor()) {
                $objWriter->startElement('color');
                $objWriter->writeAttribute('rgb', $dataBar->getColor());
                $objWriter->endElement();
            }
            $objWriter->endElement(); // end dataBar

            if ($dataBar->getConditionalFormattingRuleExt()) {
                $objWriter->startElement('extLst');
                $extension = $dataBar->getConditionalFormattingRuleExt();
                $objWriter->startElement('ext');
                $objWriter->writeAttribute('uri', '{B025F937-C7B1-47D3-B67F-A62EFF666E3E}');
                $objWriter->startElementNs('x14', 'id', null);
                $objWriter->text($extension->getId());
                $objWriter->endElement();
                $objWriter->endElement();
                $objWriter->endElement(); //end extLst
            }
        }
    }

    private static function writeColorScaleElements(XMLWriter $objWriter, ?ConditionalColorScale $colorScale): void
    {
        if ($colorScale) {
            $objWriter->startElement('colorScale');

            $minCfvo = $colorScale->getMinimumConditionalFormatValueObject();
            $minArgb = $colorScale->getMinimumColor()?->getARGB();
            $useMin = $minCfvo !== null || $minArgb !== null;
            if ($useMin) {
                $objWriter->startElement('cfvo');
                $type = 'min';
                $value = null;
                if ($minCfvo !== null) {
                    $typex = $minCfvo->getType();
                    if ($typex === 'formula') {
                        $value = $minCfvo->getCellFormula();
                        if ($value !== null) {
                            $type = $typex;
                        }
                    } else {
                        $type = $typex;
                        $defaults = ['number' => '0', 'percent' => '0', 'percentile' => '10'];
                        $value = $minCfvo->getValue() ?? $defaults[$type] ?? null;
                    }
                }
                $objWriter->writeAttribute('type', $type);
                self::writeAttributeIf($objWriter, $value !== null, 'val', (string) $value);
                $objWriter->endElement();
            }
            $midCfvo = $colorScale->getMidpointConditionalFormatValueObject();
            $midArgb = $colorScale->getMidpointColor()?->getARGB();
            $useMid = $midCfvo !== null || $midArgb !== null;
            if ($useMid) {
                $objWriter->startElement('cfvo');
                $type = 'percentile';
                $value = '50';
                if ($midCfvo !== null) {
                    $type = $midCfvo->getType();
                    if ($type === 'formula') {
                        $value = $midCfvo->getCellFormula();
                        if ($value === null) {
                            $type = 'percentile';
                            $value = '50';
                        }
                    } else {
                        $defaults = ['number' => '0', 'percent' => '50', 'percentile' => '50'];
                        $value = $midCfvo->getValue() ?? $defaults[$type] ?? null;
                    }
                }
                $objWriter->writeAttribute('type', $type);
                self::writeAttributeIf($objWriter, $value !== null, 'val', (string) $value);
                $objWriter->endElement();
            }
            $maxCfvo = $colorScale->getMaximumConditionalFormatValueObject();
            $maxArgb = $colorScale->getMaximumColor()?->getARGB();
            $useMax = $maxCfvo !== null || $maxArgb !== null;
            if ($useMax) {
                $objWriter->startElement('cfvo');
                $type = 'max';
                $value = null;
                if ($maxCfvo !== null) {
                    $typex = $maxCfvo->getType();
                    if ($typex === 'formula') {
                        $value = $maxCfvo->getCellFormula();
                        if ($value !== null) {
                            $type = $typex;
                        }
                    } else {
                        $type = $typex;
                        $defaults = ['number' => '0', 'percent' => '100', 'percentile' => '90'];
                        $value = $maxCfvo->getValue() ?? $defaults[$type] ?? null;
                    }
                }
                $objWriter->writeAttribute('type', $type);
                self::writeAttributeIf($objWriter, $value !== null, 'val', (string) $value);
                $objWriter->endElement();
            }
            if ($useMin) {
                $objWriter->startElement('color');
                self::writeAttributeIf($objWriter, $minArgb !== null, 'rgb', "$minArgb");
                $objWriter->endElement();
            }
            if ($useMid) {
                $objWriter->startElement('color');
                self::writeAttributeIf($objWriter, $midArgb !== null, 'rgb', "$midArgb");
                $objWriter->endElement();
            }
            if ($useMax) {
                $objWriter->startElement('color');
                self::writeAttributeIf($objWriter, $maxArgb !== null, 'rgb', "$maxArgb");
                $objWriter->endElement();
            }
            $objWriter->endElement(); // end colorScale
        }
    }

    /**
     * Write ConditionalFormatting.
     */
    private function writeConditionalFormatting(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        // Conditional id
        $id = 0;
        foreach ($worksheet->getConditionalStylesCollection() as $conditionalStyles) {
            foreach ($conditionalStyles as $conditional) {
                $id = max($id, $conditional->getPriority());
            }
        }

        // Loop through styles in the current worksheet
        foreach ($worksheet->getConditionalStylesCollection() as $cellCoordinate => $conditionalStyles) {
            $objWriter->startElement('conditionalFormatting');
            // N.B. In Excel UI, intersection is space and union is comma.
            // But in Xml, intersection is comma and union is space.
            // Anyhow, I don't think Excel handles intersection correctly when reading.
            $outCoordinate = Coordinate::resolveUnionAndIntersection(str_replace('$', '', $cellCoordinate), ' ');
            $objWriter->writeAttribute('sqref', $outCoordinate);

            foreach ($conditionalStyles as $conditional) {
                // WHY was this again?
                // if ($this->getParentWriter()->getStylesConditionalHashTable()->getIndexForHashCode($conditional->getHashCode()) == '') {
                //    continue;
                // }
                // cfRule
                $objWriter->startElement('cfRule');
                $objWriter->writeAttribute('type', $conditional->getConditionType());
                self::writeAttributeIf(
                    $objWriter,
                    ($conditional->getConditionType() !== Conditional::CONDITION_COLORSCALE
                        && $conditional->getConditionType() !== Conditional::CONDITION_DATABAR
                        && $conditional->getNoFormatSet() === false),
                    'dxfId',
                    (string) $this->getParentWriter()->getStylesConditionalHashTable()->getIndexForHashCode($conditional->getHashCode())
                );
                $priority = $conditional->getPriority() ?: ++$id;
                $objWriter->writeAttribute('priority', (string) $priority);

                self::writeAttributeif(
                    $objWriter,
                    (
                        $conditional->getConditionType() === Conditional::CONDITION_CELLIS
                        || $conditional->getConditionType() === Conditional::CONDITION_CONTAINSTEXT
                        || $conditional->getConditionType() === Conditional::CONDITION_NOTCONTAINSTEXT
                        || $conditional->getConditionType() === Conditional::CONDITION_BEGINSWITH
                        || $conditional->getConditionType() === Conditional::CONDITION_ENDSWITH
                    ) && $conditional->getOperatorType() !== Conditional::OPERATOR_NONE,
                    'operator',
                    $conditional->getOperatorType()
                );

                self::writeAttributeIf($objWriter, $conditional->getStopIfTrue(), 'stopIfTrue', '1');

                $cellRange = Coordinate::splitRange(str_replace('$', '', strtoupper($cellCoordinate)));
                [$topLeftCell] = $cellRange[0];

                if (
                    $conditional->getConditionType() === Conditional::CONDITION_CONTAINSTEXT
                    || $conditional->getConditionType() === Conditional::CONDITION_NOTCONTAINSTEXT
                    || $conditional->getConditionType() === Conditional::CONDITION_BEGINSWITH
                    || $conditional->getConditionType() === Conditional::CONDITION_ENDSWITH
                ) {
                    self::writeTextCondElements($objWriter, $conditional, $topLeftCell);
                } elseif ($conditional->getConditionType() === Conditional::CONDITION_TIMEPERIOD) {
                    self::writeTimePeriodCondElements($objWriter, $conditional, $topLeftCell);
                } elseif ($conditional->getConditionType() === Conditional::CONDITION_COLORSCALE) {
                    self::writeColorScaleElements($objWriter, $conditional->getColorScale());
                } else {
                    self::writeOtherCondElements($objWriter, $conditional, $topLeftCell);
                }

                //<dataBar>
                self::writeDataBarElements($objWriter, $conditional->getDataBar());

                $objWriter->endElement(); //end cfRule
            }

            $objWriter->endElement(); //end conditionalFormatting
        }
    }

    /**
     * Write DataValidations.
     */
    private function writeDataValidations(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        // Datavalidation collection
        $dataValidationCollection = $worksheet->getDataValidationCollection();

        // Write data validations?
        if (!empty($dataValidationCollection)) {
            $objWriter->startElement('dataValidations');
            $objWriter->writeAttribute('count', (string) count($dataValidationCollection));

            foreach ($dataValidationCollection as $coordinate => $dv) {
                $objWriter->startElement('dataValidation');

                if ($dv->getType() != '') {
                    $objWriter->writeAttribute('type', $dv->getType());
                }

                if ($dv->getErrorStyle() != '') {
                    $objWriter->writeAttribute('errorStyle', $dv->getErrorStyle());
                }

                if ($dv->getOperator() != '') {
                    $objWriter->writeAttribute('operator', $dv->getOperator());
                }

                $objWriter->writeAttribute('allowBlank', ($dv->getAllowBlank() ? '1' : '0'));
                $objWriter->writeAttribute('showDropDown', (!$dv->getShowDropDown() ? '1' : '0'));
                $objWriter->writeAttribute('showInputMessage', ($dv->getShowInputMessage() ? '1' : '0'));
                $objWriter->writeAttribute('showErrorMessage', ($dv->getShowErrorMessage() ? '1' : '0'));

                if ($dv->getErrorTitle() !== '') {
                    $objWriter->writeAttribute('errorTitle', $dv->getErrorTitle());
                }
                if ($dv->getError() !== '') {
                    $objWriter->writeAttribute('error', $dv->getError());
                }
                if ($dv->getPromptTitle() !== '') {
                    $objWriter->writeAttribute('promptTitle', $dv->getPromptTitle());
                }
                if ($dv->getPrompt() !== '') {
                    $objWriter->writeAttribute('prompt', $dv->getPrompt());
                }

                $objWriter->writeAttribute('sqref', $dv->getSqref() ?? $coordinate);

                if ($dv->getFormula1() !== '') {
                    $objWriter->writeElement('formula1', FunctionPrefix::addFunctionPrefix($dv->getFormula1()));
                }
                if ($dv->getFormula2() !== '') {
                    $objWriter->writeElement('formula2', FunctionPrefix::addFunctionPrefix($dv->getFormula2()));
                }

                $objWriter->endElement();
            }

            $objWriter->endElement();
        }
    }

    /**
     * Write Hyperlinks.
     */
    private function writeHyperlinks(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        // Hyperlink collection
        $hyperlinkCollection = $worksheet->getHyperlinkCollection();

        // Relation ID
        $relationId = 1;

        // Write hyperlinks?
        if (!empty($hyperlinkCollection)) {
            $objWriter->startElement('hyperlinks');

            foreach ($hyperlinkCollection as $coordinate => $hyperlink) {
                $objWriter->startElement('hyperlink');

                $objWriter->writeAttribute('ref', $coordinate);
                if (!$hyperlink->isInternal()) {
                    $objWriter->writeAttribute('r:id', 'rId_hyperlink_' . $relationId);
                    ++$relationId;
                } else {
                    $objWriter->writeAttribute('location', str_replace('sheet://', '', $hyperlink->getUrl()));
                }

                if ($hyperlink->getTooltip() !== '') {
                    $objWriter->writeAttribute('tooltip', $hyperlink->getTooltip());
                    $objWriter->writeAttribute('display', $hyperlink->getTooltip());
                }

                $objWriter->endElement();
            }

            $objWriter->endElement();
        }
    }

    /**
     * Write ProtectedRanges.
     */
    private function writeProtectedRanges(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        if (count($worksheet->getProtectedCellRanges()) > 0) {
            // protectedRanges
            $objWriter->startElement('protectedRanges');

            // Loop protectedRanges
            foreach ($worksheet->getProtectedCellRanges() as $protectedCell => $protectedRange) {
                // protectedRange
                $objWriter->startElement('protectedRange');
                $objWriter->writeAttribute('name', $protectedRange->getName());
                $objWriter->writeAttribute('sqref', $protectedCell);
                $passwordHash = $protectedRange->getPassword();
                $this->writeAttributeIf($objWriter, $passwordHash !== '', 'password', $passwordHash);
                $securityDescriptor = $protectedRange->getSecurityDescriptor();
                $this->writeAttributeIf($objWriter, $securityDescriptor !== '', 'securityDescriptor', $securityDescriptor);
                $objWriter->endElement();
            }

            $objWriter->endElement();
        }
    }

    /**
     * Write MergeCells.
     */
    private function writeMergeCells(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        if (count($worksheet->getMergeCells()) > 0) {
            // mergeCells
            $objWriter->startElement('mergeCells');

            // Loop mergeCells
            foreach ($worksheet->getMergeCells() as $mergeCell) {
                // mergeCell
                $objWriter->startElement('mergeCell');
                $objWriter->writeAttribute('ref', $mergeCell);
                $objWriter->endElement();
            }

            $objWriter->endElement();
        }
    }

    /**
     * Write PrintOptions.
     */
    private function writePrintOptions(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        // printOptions
        $objWriter->startElement('printOptions');

        $objWriter->writeAttribute('gridLines', ($worksheet->getPrintGridlines() ? 'true' : 'false'));
        $objWriter->writeAttribute('gridLinesSet', 'true');

        if ($worksheet->getPageSetup()->getHorizontalCentered()) {
            $objWriter->writeAttribute('horizontalCentered', 'true');
        }

        if ($worksheet->getPageSetup()->getVerticalCentered()) {
            $objWriter->writeAttribute('verticalCentered', 'true');
        }

        $objWriter->endElement();
    }

    /**
     * Write PageMargins.
     */
    private function writePageMargins(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        // pageMargins
        $objWriter->startElement('pageMargins');
        $objWriter->writeAttribute('left', StringHelper::formatNumber($worksheet->getPageMargins()->getLeft()));
        $objWriter->writeAttribute('right', StringHelper::formatNumber($worksheet->getPageMargins()->getRight()));
        $objWriter->writeAttribute('top', StringHelper::formatNumber($worksheet->getPageMargins()->getTop()));
        $objWriter->writeAttribute('bottom', StringHelper::formatNumber($worksheet->getPageMargins()->getBottom()));
        $objWriter->writeAttribute('header', StringHelper::formatNumber($worksheet->getPageMargins()->getHeader()));
        $objWriter->writeAttribute('footer', StringHelper::formatNumber($worksheet->getPageMargins()->getFooter()));
        $objWriter->endElement();
    }

    /**
     * Write AutoFilter.
     */
    private function writeAutoFilter(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        AutoFilter::writeAutoFilter($objWriter, $worksheet);
    }

    /**
     * Write Table.
     */
    private function writeTable(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        $tableCount = $worksheet->getTableCollection()->count();
        if ($tableCount === 0) {
            return;
        }

        $objWriter->startElement('tableParts');
        $objWriter->writeAttribute('count', (string) $tableCount);

        for ($t = 1; $t <= $tableCount; ++$t) {
            $objWriter->startElement('tablePart');
            $objWriter->writeAttribute('r:id', 'rId_table_' . $t);
            $objWriter->endElement();
        }

        $objWriter->endElement();
    }

    /**
     * Write Background Image.
     */
    private function writeBackgroundImage(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        if ($worksheet->getBackgroundImage() !== '') {
            $objWriter->startElement('picture');
            $objWriter->writeAttribute('r:id', 'rIdBg');
            $objWriter->endElement();
        }
    }

    /**
     * Write PageSetup.
     */
    private function writePageSetup(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        // pageSetup
        $objWriter->startElement('pageSetup');
        $objWriter->writeAttribute('paperSize', (string) $worksheet->getPageSetup()->getPaperSize());
        $objWriter->writeAttribute('orientation', $worksheet->getPageSetup()->getOrientation());

        if ($worksheet->getPageSetup()->getScale() !== null) {
            $objWriter->writeAttribute('scale', (string) $worksheet->getPageSetup()->getScale());
        }
        if ($worksheet->getPageSetup()->getFitToHeight() !== null) {
            $objWriter->writeAttribute('fitToHeight', (string) $worksheet->getPageSetup()->getFitToHeight());
        } else {
            $objWriter->writeAttribute('fitToHeight', '0');
        }
        if ($worksheet->getPageSetup()->getFitToWidth() !== null) {
            $objWriter->writeAttribute('fitToWidth', (string) $worksheet->getPageSetup()->getFitToWidth());
        } else {
            $objWriter->writeAttribute('fitToWidth', '0');
        }
        if (!empty($worksheet->getPageSetup()->getFirstPageNumber())) {
            $objWriter->writeAttribute('firstPageNumber', (string) $worksheet->getPageSetup()->getFirstPageNumber());
            $objWriter->writeAttribute('useFirstPageNumber', '1');
        }
        $objWriter->writeAttribute('pageOrder', $worksheet->getPageSetup()->getPageOrder());

        /** @var string[][][] */
        $getUnparsedLoadedData = $worksheet->getParentOrThrow()->getUnparsedLoadedData();
        if (isset($getUnparsedLoadedData['sheets'][$worksheet->getCodeName()]['pageSetupRelId'])) {
            $objWriter->writeAttribute('r:id', $getUnparsedLoadedData['sheets'][$worksheet->getCodeName()]['pageSetupRelId']);
        }

        $objWriter->endElement();
    }

    /**
     * Write Header / Footer.
     */
    private function writeHeaderFooter(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        // headerFooter
        $headerFooter = $worksheet->getHeaderFooter();
        $oddHeader = $headerFooter->getOddHeader();
        $oddFooter = $headerFooter->getOddFooter();
        $evenHeader = $headerFooter->getEvenHeader();
        $evenFooter = $headerFooter->getEvenFooter();
        $firstHeader = $headerFooter->getFirstHeader();
        $firstFooter = $headerFooter->getFirstFooter();
        if ("$oddHeader$oddFooter$evenHeader$evenFooter$firstHeader$firstFooter" === '') {
            return;
        }

        $objWriter->startElement('headerFooter');
        $objWriter->writeAttribute('differentOddEven', ($worksheet->getHeaderFooter()->getDifferentOddEven() ? 'true' : 'false'));
        $objWriter->writeAttribute('differentFirst', ($worksheet->getHeaderFooter()->getDifferentFirst() ? 'true' : 'false'));
        $objWriter->writeAttribute('scaleWithDoc', ($worksheet->getHeaderFooter()->getScaleWithDocument() ? 'true' : 'false'));
        $objWriter->writeAttribute('alignWithMargins', ($worksheet->getHeaderFooter()->getAlignWithMargins() ? 'true' : 'false'));

        self::writeElementIf($objWriter, $oddHeader !== '', 'oddHeader', $oddHeader);
        self::writeElementIf($objWriter, $oddFooter !== '', 'oddFooter', $oddFooter);
        self::writeElementIf($objWriter, $evenHeader !== '', 'evenHeader', $evenHeader);
        self::writeElementIf($objWriter, $evenFooter !== '', 'evenFooter', $evenFooter);
        self::writeElementIf($objWriter, $firstHeader !== '', 'firstHeader', $firstHeader);
        self::writeElementIf($objWriter, $firstFooter !== '', 'firstFooter', $firstFooter);

        $objWriter->endElement(); // headerFooter
    }

    /**
     * Write Breaks.
     */
    private function writeBreaks(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        // Get row and column breaks
        $aRowBreaks = [];
        $aColumnBreaks = [];
        foreach ($worksheet->getRowBreaks() as $cell => $break) {
            $aRowBreaks[$cell] = $break;
        }
        foreach ($worksheet->getColumnBreaks() as $cell => $break) {
            $aColumnBreaks[$cell] = $break;
        }

        // rowBreaks
        if (!empty($aRowBreaks)) {
            $objWriter->startElement('rowBreaks');
            $objWriter->writeAttribute('count', (string) count($aRowBreaks));
            $objWriter->writeAttribute('manualBreakCount', (string) count($aRowBreaks));

            foreach ($aRowBreaks as $cell => $break) {
                $coords = Coordinate::coordinateFromString($cell);

                $objWriter->startElement('brk');
                $objWriter->writeAttribute('id', $coords[1]);
                $objWriter->writeAttribute('man', '1');
                $rowBreakMax = $break->getMaxColOrRow();
                if ($rowBreakMax >= 0) {
                    $objWriter->writeAttribute('max', "$rowBreakMax");
                } elseif ($worksheet->getPageSetup()->getPrintArea() !== '') {
                    $maxCol = Coordinate::columnIndexFromString($worksheet->getHighestColumn());
                    $objWriter->writeAttribute('max', "$maxCol");
                }
                $objWriter->endElement();
            }

            $objWriter->endElement();
        }

        // Second, write column breaks
        if (!empty($aColumnBreaks)) {
            $objWriter->startElement('colBreaks');
            $objWriter->writeAttribute('count', (string) count($aColumnBreaks));
            $objWriter->writeAttribute('manualBreakCount', (string) count($aColumnBreaks));

            foreach ($aColumnBreaks as $cell => $break) {
                $coords = Coordinate::indexesFromString($cell);

                $objWriter->startElement('brk');
                $objWriter->writeAttribute('id', (string) ((int) $coords[0] - 1));
                $objWriter->writeAttribute('man', '1');
                $colBreakMax = $break->getMaxColOrRow();
                if ($colBreakMax >= 0) {
                    $objWriter->writeAttribute('max', "$colBreakMax");
                } elseif ($worksheet->getPageSetup()->getPrintArea() !== '') {
                    $maxRow = $worksheet->getHighestRow();
                    $objWriter->writeAttribute('max', "$maxRow");
                }
                $objWriter->endElement();
            }

            $objWriter->endElement();
        }
    }

    /**
     * Write SheetData.
     *
     * @param string[] $stringTable String table
     */
    private function writeSheetData(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet, array $stringTable): void
    {
        // Flipped stringtable, for faster index searching
        $aFlippedStringTable = $this->getParentWriter()->getWriterPartstringtable()->flipStringTable($stringTable);

        // sheetData
        $objWriter->startElement('sheetData');

        // Get column count
        $colCount = Coordinate::columnIndexFromString($worksheet->getHighestColumn());

        // Highest row number
        $highestRow = $worksheet->getHighestRow();

        // Loop through cells building a comma-separated list of the columns in each row
        // This is a trade-off between the memory usage that is required for a full array of columns,
        //      and execution speed
        /** @var array<int, string> $cellsByRow */
        $cellsByRow = [];
        foreach ($worksheet->getCoordinates() as $coordinate) {
            [$column, $row] = Coordinate::coordinateFromString($coordinate);
            if (!isset($cellsByRow[$row])) {
                $pCell = $worksheet->getCell("$column$row");
                $xfi = $pCell->getXfIndex();
                $cellValue = $pCell->getValue();
                $writeValue = $cellValue !== '' && $cellValue !== null;
                if (!empty($xfi) || $writeValue) {
                    $cellsByRow[$row] = "{$column},";
                }
            } else {
                $cellsByRow[$row] .= "{$column},";
            }
        }

        $currentRow = 0;
        $emptyDimension = new RowDimension();
        while ($currentRow++ < $highestRow) {
            $isRowSet = isset($cellsByRow[$currentRow]);
            if ($isRowSet || $worksheet->rowDimensionExists($currentRow)) {
                // Get row dimension
                $rowDimension = $worksheet->rowDimensionExists($currentRow) ? $worksheet->getRowDimension($currentRow) : $emptyDimension;

                // Write current row?
                $writeCurrentRow = $isRowSet || $rowDimension->getRowHeight() >= 0 || $rowDimension->getVisible() === false || $rowDimension->getCollapsed() === true || $rowDimension->getOutlineLevel() > 0 || $rowDimension->getXfIndex() !== null;

                if ($writeCurrentRow) {
                    // Start a new row
                    $objWriter->startElement('row');
                    $objWriter->writeAttribute('r', "$currentRow");
                    $objWriter->writeAttribute('spans', '1:' . $colCount);

                    // Row dimensions
                    if ($rowDimension->getRowHeight() >= 0) {
                        $objWriter->writeAttribute('customHeight', '1');
                        $objWriter->writeAttribute('ht', StringHelper::formatNumber($rowDimension->getRowHeight()));
                    }

                    // Row visibility
                    if (!$rowDimension->getVisible() === true) {
                        $objWriter->writeAttribute('hidden', 'true');
                    }

                    // Collapsed
                    if ($rowDimension->getCollapsed() === true) {
                        $objWriter->writeAttribute('collapsed', 'true');
                    }

                    // Outline level
                    if ($rowDimension->getOutlineLevel() > 0) {
                        $objWriter->writeAttribute('outlineLevel', (string) $rowDimension->getOutlineLevel());
                    }

                    // Style
                    if ($rowDimension->getXfIndex() !== null) {
                        $objWriter->writeAttribute('s', (string) $rowDimension->getXfIndex());
                        $objWriter->writeAttribute('customFormat', '1');
                    }

                    // Write cells
                    if (isset($cellsByRow[$currentRow])) {
                        // We have a comma-separated list of column names (with a trailing entry); split to an array
                        $columnsInRow = explode(',', $cellsByRow[$currentRow]);
                        array_pop($columnsInRow);
                        foreach ($columnsInRow as $column) {
                            // Write cell
                            $coord = "$column$currentRow";
                            if ($worksheet->getCell($coord)->getIgnoredErrors()->getNumberStoredAsText()) {
                                $this->numberStoredAsText .= " $coord";
                            }
                            if ($worksheet->getCell($coord)->getIgnoredErrors()->getFormula()) {
                                $this->formula .= " $coord";
                            }
                            if ($worksheet->getCell($coord)->getIgnoredErrors()->getFormulaRange()) {
                                $this->formulaRange .= " $coord";
                            }
                            if ($worksheet->getCell($coord)->getIgnoredErrors()->getTwoDigitTextYear()) {
                                $this->twoDigitTextYear .= " $coord";
                            }
                            if ($worksheet->getCell($coord)->getIgnoredErrors()->getEvalError()) {
                                $this->evalError .= " $coord";
                            }
                            $this->writeCell($objWriter, $worksheet, $coord, $aFlippedStringTable);
                        }
                    }

                    // End row
                    $objWriter->endElement();
                }
            }
        }

        $objWriter->endElement();
    }

    private function writeCellInlineStr(XMLWriter $objWriter, string $mappedType, RichText|string $cellValue, ?Font $font): void
    {
        $objWriter->writeAttribute('t', $mappedType);
        if (!$cellValue instanceof RichText) {
            $objWriter->startElement('is');
            $objWriter->writeElement(
                't',
                StringHelper::controlCharacterPHP2OOXML(
                    $cellValue
                )
            );
            $objWriter->endElement();
        } else {
            $objWriter->startElement('is');
            $this->getParentWriter()
                ->getWriterPartstringtable()
                ->writeRichText($objWriter, $cellValue, null, $font);
            $objWriter->endElement();
        }
    }

    /**
     * @param string[] $flippedStringTable
     */
    private function writeCellString(XMLWriter $objWriter, string $mappedType, RichText|string $cellValue, array $flippedStringTable): void
    {
        $objWriter->writeAttribute('t', $mappedType);
        if (!$cellValue instanceof RichText) {
            self::writeElementIf($objWriter, isset($flippedStringTable[$cellValue]), 'v', $flippedStringTable[$cellValue] ?? '');
        } else {
            $objWriter->writeElement('v', $flippedStringTable[$cellValue->getHashCode()]);
        }
    }

    private function writeCellNumeric(XMLWriter $objWriter, float|int $cellValue): void
    {
        $result = StringHelper::convertToString($cellValue);
        if (is_float($cellValue) && !str_contains($result, '.')) {
            $result .= '.0';
        }
        $objWriter->writeElement('v', $result);
    }

    private function writeCellBoolean(XMLWriter $objWriter, string $mappedType, bool $cellValue): void
    {
        $objWriter->writeAttribute('t', $mappedType);
        $objWriter->writeElement('v', $cellValue ? '1' : '0');
    }

    private function writeCellError(XMLWriter $objWriter, string $mappedType, string $cellValue, string $formulaerr = '#NULL!'): void
    {
        $objWriter->writeAttribute('t', $mappedType);
        $cellIsFormula = str_starts_with($cellValue, '=');
        self::writeElementIf($objWriter, $cellIsFormula, 'f', FunctionPrefix::addFunctionPrefixStripEquals($cellValue));
        $objWriter->writeElement('v', $cellIsFormula ? $formulaerr : $cellValue);
    }

    private function writeCellFormula(XMLWriter $objWriter, string $cellValue, Cell $cell): void
    {
        $attributes = $cell->getFormulaAttributes() ?? [];
        $coordinate = $cell->getCoordinate();
        $calculatedValue = $this->getParentWriter()->getPreCalculateFormulas() ? $cell->getCalculatedValue() : $cellValue;
        if ($calculatedValue === ExcelError::SPILL()) {
            $objWriter->writeAttribute('t', 'e');
            //$objWriter->writeAttribute('cm', '1'); // already added
            $objWriter->writeAttribute('vm', '1');
            $objWriter->startElement('f');
            $objWriter->writeAttribute('t', 'array');
            $objWriter->writeAttribute('aca', '1');
            $objWriter->writeAttribute('ref', $coordinate);
            $objWriter->writeAttribute('ca', '1');
            $objWriter->text(FunctionPrefix::addFunctionPrefixStripEquals($cellValue));
            $objWriter->endElement(); // f
            $objWriter->writeElement('v', ExcelError::VALUE()); // note #VALUE! in xml even though error is #SPILL!

            return;
        }
        $calculatedValueString = $this->getParentWriter()->getPreCalculateFormulas() ? $cell->getCalculatedValueString() : $cellValue;
        $result = $calculatedValue;
        while (is_array($result)) {
            $result = array_shift($result);
        }
        if (is_string($result)) {
            if (ErrorValue::isError($result)) {
                $this->writeCellError($objWriter, 'e', $cellValue, $result);

                return;
            }
            $objWriter->writeAttribute('t', 'str');
            $result = $calculatedValueString = StringHelper::controlCharacterPHP2OOXML($result);
            if (is_string($calculatedValue)) {
                $calculatedValue = $calculatedValueString;
            }
        } elseif (is_bool($result)) {
            $objWriter->writeAttribute('t', 'b');
            if (is_bool($calculatedValue)) {
                $calculatedValue = $result;
            }
            $result = (int) $result;
            $calculatedValueString = (string) $result;
        }

        if (isset($attributes['ref'])) {
            $ref = $this->parseRef($coordinate, $attributes['ref']);
            if ($ref === "$coordinate:$coordinate") {
                $ref = $coordinate;
            }
        } else {
            $ref = $coordinate;
        }
        if (is_array($calculatedValue)) {
            $attributes['t'] = 'array';
        }
        if (($attributes['t'] ?? null) === 'array') {
            $objWriter->startElement('f');
            $objWriter->writeAttribute('t', 'array');
            $objWriter->writeAttribute('ref', $ref);
            $objWriter->writeAttribute('aca', '1');
            $objWriter->writeAttribute('ca', '1');
            $objWriter->text(FunctionPrefix::addFunctionPrefixStripEquals($cellValue));
            $objWriter->endElement();
            if (
                is_scalar($result)
                && $this->getParentWriter()->getOffice2003Compatibility() === false
                && $this->getParentWriter()->getPreCalculateFormulas()
            ) {
                $objWriter->writeElement('v', (string) $result);
            }
        } else {
            $objWriter->writeElement('f', FunctionPrefix::addFunctionPrefixStripEquals($cellValue));
            self::writeElementIf(
                $objWriter,
                $this->getParentWriter()->getOffice2003Compatibility() === false
                && $this->getParentWriter()->getPreCalculateFormulas()
                && $calculatedValue !== null,
                'v',
                (!is_array($calculatedValue) && !str_starts_with($calculatedValueString, '#'))
                    ? StringHelper::formatNumber($calculatedValueString) : '0'
            );
        }
    }

    private function parseRef(string $coordinate, string $ref): string
    {
        if (!Preg::isMatch('/^([A-Z]{1,3})([0-9]{1,7})(:([A-Z]{1,3})([0-9]{1,7}))?$/', $ref, $matches)) {
            return $ref;
        }
        if (!isset($matches[3])) { // single cell, not range
            return $coordinate;
        }
        $minRow = (int) $matches[2];
        $maxRow = (int) $matches[5];
        $rows = $maxRow - $minRow + 1;
        $minCol = Coordinate::columnIndexFromString($matches[1]);
        $maxCol = Coordinate::columnIndexFromString($matches[4]);
        $cols = $maxCol - $minCol + 1;
        $firstCellArray = Coordinate::indexesFromString($coordinate);
        $lastRow = $firstCellArray[1] + $rows - 1;
        $lastColumn = $firstCellArray[0] + $cols - 1;
        $lastColumnString = Coordinate::stringFromColumnIndex($lastColumn);

        return "$coordinate:$lastColumnString$lastRow";
    }

    /**
     * Write Cell.
     *
     * @param string $cellAddress Cell Address
     * @param string[] $flippedStringTable String table (flipped), for faster index searching
     */
    private function writeCell(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet, string $cellAddress, array $flippedStringTable): void
    {
        // Cell
        $pCell = $worksheet->getCell($cellAddress);
        $xfi = $pCell->getXfIndex();
        $cellValue = $pCell->getValue();
        $cellValueString = $pCell->getValueString();
        $writeValue = $cellValue !== '' && $cellValue !== null;
        if (empty($xfi) && !$writeValue) {
            return;
        }
        $styleArray = $this->getParentWriter()
            ->getSpreadsheet()
            ->getCellXfCollection();
        $font = $styleArray[$xfi] ?? null;
        if ($font !== null) {
            $font = $font->getFont();
        }
        $objWriter->startElement('c');
        $objWriter->writeAttribute('r', $cellAddress);
        $mappedType = $pCell->getDataType();
        if ($mappedType === DataType::TYPE_FORMULA) {
            if ($this->useDynamicArrays) {
                if (preg_match(PhpspreadsheetWorksheet::FUNCTION_LIKE_GROUPBY, $cellValueString) === 1) {
                    $tempCalc = [];
                } else {
                    $tempCalc = $pCell->getCalculatedValue();
                }
                if (is_array($tempCalc)) {
                    $objWriter->writeAttribute('cm', '1');
                }
            }
        }

        // Sheet styles
        if ($xfi) {
            $objWriter->writeAttribute('s', "$xfi");
        } elseif ($this->explicitStyle0) {
            $objWriter->writeAttribute('s', '0');
        }

        // If cell value is supplied, write cell value
        if ($writeValue) {
            // Write data depending on its type
            switch (strtolower($mappedType)) {
                case 'inlinestr':    // Inline string
                    /** @var RichText|string */
                    $richText = $cellValue;
                    $this->writeCellInlineStr($objWriter, $mappedType, $richText, $font);

                    break;
                case 's':            // String
                    $this->writeCellString($objWriter, $mappedType, ($cellValue instanceof RichText) ? $cellValue : $cellValueString, $flippedStringTable);

                    break;
                case 'f':            // Formula
                    $this->writeCellFormula($objWriter, $cellValueString, $pCell);

                    break;
                case 'n':            // Numeric
                    $cellValueNumeric = is_numeric($cellValue) ? ($cellValue + 0) : 0;
                    $this->writeCellNumeric($objWriter, $cellValueNumeric);

                    break;
                case 'b':            // Boolean
                    $this->writeCellBoolean($objWriter, $mappedType, (bool) $cellValue);

                    break;
                case 'e':            // Error
                    $this->writeCellError($objWriter, $mappedType, $cellValueString);
            }
        }

        $objWriter->endElement(); // c
    }

    /**
     * Write Drawings.
     *
     * @param bool $includeCharts Flag indicating if we should include drawing details for charts
     */
    private function writeDrawings(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet, bool $includeCharts = false): void
    {
        /** @var mixed[][][][] */
        $unparsedLoadedData = $worksheet->getParentOrThrow()->getUnparsedLoadedData();
        $hasUnparsedDrawing = isset($unparsedLoadedData['sheets'][$worksheet->getCodeName()]['drawingOriginalIds']);
        $chartCount = ($includeCharts) ? $worksheet->getChartCollection()->count() : 0;
        if ($chartCount == 0 && $worksheet->getDrawingCollection()->count() == 0 && !$hasUnparsedDrawing) {
            return;
        }

        // If sheet contains drawings, add the relationships
        $objWriter->startElement('drawing');

        $rId = 'rId1';
        if (isset($unparsedLoadedData['sheets'][$worksheet->getCodeName()]['drawingOriginalIds'])) {
            $drawingOriginalIds = $unparsedLoadedData['sheets'][$worksheet->getCodeName()]['drawingOriginalIds'];
            // take first. In future can be overriten
            // (! synchronize with \PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels::writeWorksheetRelationships)
            $rId = reset($drawingOriginalIds);
        }

        /** @var string $rId */
        $objWriter->writeAttribute('r:id', $rId);
        $objWriter->endElement();
    }

    /**
     * Write LegacyDrawing.
     */
    private function writeLegacyDrawing(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        // If sheet contains comments, add the relationships
        /** @var mixed[][][][] */
        $unparsedLoadedData = $worksheet->getParentOrThrow()->getUnparsedLoadedData();
        if (count($worksheet->getComments()) > 0 || isset($unparsedLoadedData['sheets'][$worksheet->getCodeName()]['legacyDrawing'])) {
            $objWriter->startElement('legacyDrawing');
            $objWriter->writeAttribute('r:id', 'rId_comments_vml1');
            $objWriter->endElement();
        }
    }

    /**
     * Write LegacyDrawingHF.
     */
    private function writeLegacyDrawingHF(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        // If sheet contains images, add the relationships
        if (count($worksheet->getHeaderFooter()->getImages()) > 0) {
            $objWriter->startElement('legacyDrawingHF');
            $objWriter->writeAttribute('r:id', 'rId_headerfooter_vml1');
            $objWriter->endElement();
        }
    }

    private function writeAlternateContent(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        /** @var string[][][] */
        $unparsedSheet = $worksheet->getParentOrThrow()->getUnparsedLoadedData()['sheets'] ?? [];
        $unparsedSheet = $unparsedSheet[$worksheet->getCodeName()] ?? [];
        $unparsedSheet = $unparsedSheet['AlternateContents'] ?? [];

        foreach ($unparsedSheet as $alternateContent) {
            $objWriter->writeRaw($alternateContent);
        }
    }

    /**
     * write <ExtLst>
     * only implementation conditionalFormattings.
     *
     * @url https://docs.microsoft.com/en-us/openspecs/office_standards/ms-xlsx/07d607af-5618-4ca2-b683-6a78dc0d9627
     */
    private function writeExtLst(XMLWriter $objWriter, PhpspreadsheetWorksheet $worksheet): void
    {
        $conditionalFormattingRuleExtList = [];
        foreach ($worksheet->getConditionalStylesCollection() as $cellCoordinate => $conditionalStyles) {
            /** @var Conditional $conditional */
            foreach ($conditionalStyles as $conditional) {
                $dataBar = $conditional->getDataBar();
                if ($dataBar && $dataBar->getConditionalFormattingRuleExt()) {
                    $conditionalFormattingRuleExtList[] = $dataBar->getConditionalFormattingRuleExt();
                }
            }
        }

        if (count($conditionalFormattingRuleExtList) > 0) {
            $conditionalFormattingRuleExtNsPrefix = 'x14';
            $objWriter->startElement('extLst');
            $objWriter->startElement('ext');
            $objWriter->writeAttribute('uri', '{78C0D931-6437-407d-A8EE-F0AAD7539E65}');
            $objWriter->startElementNs($conditionalFormattingRuleExtNsPrefix, 'conditionalFormattings', null);
            foreach ($conditionalFormattingRuleExtList as $extension) {
                self::writeExtConditionalFormattingElements($objWriter, $extension);
            }
            $objWriter->endElement(); //end conditionalFormattings
            $objWriter->endElement(); //end ext
            $objWriter->endElement(); //end extLst
        }
    }
}
