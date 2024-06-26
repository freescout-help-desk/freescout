<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Helper;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Provides helpers to display a table.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Саша Стаменковић <umpirsky@gmail.com>
 * @author Abdellatif Ait boudad <a.aitboudad@gmail.com>
 * @author Max Grigorian <maxakawizard@gmail.com>
 */
class Table
{
    /**
     * Table headers.
     */
    private $headers = [];

    /**
     * Table rows.
     */
    private $rows = [];

    /**
     * Column widths cache.
     */
    private $effectiveColumnWidths = [];

    /**
     * Number of columns cache.
     *
     * @var int
     */
    private $numberOfColumns;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var TableStyle
     */
    private $style;

    /**
     * @var array
     */
    private $columnStyles = [];

    /**
     * User set column widths.
     *
     * @var array
     */
    private $columnWidths = [];

    private static $styles;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;

        if (!self::$styles) {
            self::$styles = self::initStyles();
        }

        $this->setStyle('default');
    }

    /**
     * Sets a style definition.
     *
     * @param string     $name  The style name
     * @param TableStyle $style A TableStyle instance
     */
    public static function setStyleDefinition($name, TableStyle $style)
    {
        if (!self::$styles) {
            self::$styles = self::initStyles();
        }

        self::$styles[$name] = $style;
    }

    /**
     * Gets a style definition by name.
     *
     * @param string $name The style name
     *
     * @return TableStyle
     */
    public static function getStyleDefinition($name)
    {
        if (!self::$styles) {
            self::$styles = self::initStyles();
        }

        if (isset(self::$styles[$name])) {
            return self::$styles[$name];
        }

        throw new InvalidArgumentException(sprintf('Style "%s" is not defined.', $name));
    }

    /**
     * Sets table style.
     *
     * @param TableStyle|string $name The style name or a TableStyle instance
     *
     * @return $this
     */
    public function setStyle($name)
    {
        $this->style = $this->resolveStyle($name);

        return $this;
    }

    /**
     * Gets the current table style.
     *
     * @return TableStyle
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * Sets table column style.
     *
     * @param int               $columnIndex Column index
     * @param TableStyle|string $name        The style name or a TableStyle instance
     *
     * @return $this
     */
    public function setColumnStyle($columnIndex, $name)
    {
        $columnIndex = (int) $columnIndex;

        $this->columnStyles[$columnIndex] = $this->resolveStyle($name);

        return $this;
    }

    /**
     * Gets the current style for a column.
     *
     * If style was not set, it returns the global table style.
     *
     * @param int $columnIndex Column index
     *
     * @return TableStyle
     */
    public function getColumnStyle($columnIndex)
    {
        if (isset($this->columnStyles[$columnIndex])) {
            return $this->columnStyles[$columnIndex];
        }

        return $this->getStyle();
    }

    /**
     * Sets the minimum width of a column.
     *
     * @param int $columnIndex Column index
     * @param int $width       Minimum column width in characters
     *
     * @return $this
     */
    public function setColumnWidth($columnIndex, $width)
    {
        $this->columnWidths[(int) $columnIndex] = (int) $width;

        return $this;
    }

    /**
     * Sets the minimum width of all columns.
     *
     * @return $this
     */
    public function setColumnWidths(array $widths)
    {
        $this->columnWidths = [];
        foreach ($widths as $index => $width) {
            $this->setColumnWidth($index, $width);
        }

        return $this;
    }

    public function setHeaders(array $headers)
    {
        $headers = array_values($headers);
        if (!empty($headers) && !\is_array($headers[0])) {
            $headers = [$headers];
        }

        $this->headers = $headers;

        return $this;
    }

    public function setRows(array $rows)
    {
        $this->rows = [];

        return $this->addRows($rows);
    }

    public function addRows(array $rows)
    {
        foreach ($rows as $row) {
            $this->addRow($row);
        }

        return $this;
    }

    public function addRow($row)
    {
        if ($row instanceof TableSeparator) {
            $this->rows[] = $row;

            return $this;
        }

        if (!\is_array($row)) {
            throw new InvalidArgumentException('A row must be an array or a TableSeparator instance.');
        }

        $this->rows[] = array_values($row);

        return $this;
    }

    public function setRow($column, array $row)
    {
        $this->rows[$column] = $row;

        return $this;
    }

    /**
     * Renders table to output.
     *
     * Example:
     *
     *     +---------------+-----------------------+------------------+
     *     | ISBN          | Title                 | Author           |
     *     +---------------+-----------------------+------------------+
     *     | 99921-58-10-7 | Divine Comedy         | Dante Alighieri  |
     *     | 9971-5-0210-0 | A Tale of Two Cities  | Charles Dickens  |
     *     | 960-425-059-0 | The Lord of the Rings | J. R. R. Tolkien |
     *     +---------------+-----------------------+------------------+
     */
    public function render()
    {
        $this->calculateNumberOfColumns();
        $rows = $this->buildTableRows($this->rows);
        $headers = $this->buildTableRows($this->headers);

        $this->calculateColumnsWidth(array_merge($headers, $rows));

        $this->renderRowSeparator();
        if (!empty($headers)) {
            foreach ($headers as $header) {
                $this->renderRow($header, $this->style->getCellHeaderFormat());
                $this->renderRowSeparator();
            }
        }
        foreach ($rows as $row) {
            if ($row instanceof TableSeparator) {
                $this->renderRowSeparator();
            } else {
                $this->renderRow($row, $this->style->getCellRowFormat());
            }
        }
        if (!empty($rows)) {
            $this->renderRowSeparator();
        }

        $this->cleanup();
    }

    /**
     * Renders horizontal header separator.
     *
     * Example:
     *
     *     +-----+-----------+-------+
     */
    private function renderRowSeparator()
    {
        if (0 === $count = $this->numberOfColumns) {
            return;
        }

        if (!$this->style->getHorizontalBorderChar() && !$this->style->getCrossingChar()) {
            return;
        }

        $markup = $this->style->getCrossingChar();
        for ($column = 0; $column < $count; ++$column) {
            $markup .= str_repeat($this->style->getHorizontalBorderChar(), $this->effectiveColumnWidths[$column]).$this->style->getCrossingChar();
        }

        $this->output->writeln(sprintf($this->style->getBorderFormat(), $markup));
    }

    /**
     * Renders vertical column separator.
     */
    private function renderColumnSeparator()
    {
        return sprintf($this->style->getBorderFormat(), $this->style->getVerticalBorderChar());
    }

    /**
     * Renders table row.
     *
     * Example:
     *
     *     | 9971-5-0210-0 | A Tale of Two Cities  | Charles Dickens  |
     *
     * @param string $cellFormat
     */
    private function renderRow(array $row, $cellFormat)
    {
        if (empty($row)) {
            return;
        }

        $rowContent = $this->renderColumnSeparator();
        foreach ($this->getRowColumns($row) as $column) {
            $rowContent .= $this->renderCell($row, $column, $cellFormat);
            $rowContent .= $this->renderColumnSeparator();
        }
        $this->output->writeln($rowContent);
    }

    /**
     * Renders table cell with padding.
     *
     * @param int    $column
     * @param string $cellFormat
     */
    private function renderCell(array $row, $column, $cellFormat)
    {
        $cell = isset($row[$column]) ? $row[$column] : '';
        $width = $this->effectiveColumnWidths[$column];
        if ($cell instanceof TableCell && $cell->getColspan() > 1) {
            // add the width of the following columns(numbers of colspan).
            foreach (range($column + 1, $column + $cell->getColspan() - 1) as $nextColumn) {
                $width += $this->getColumnSeparatorWidth() + $this->effectiveColumnWidths[$nextColumn];
            }
        }

        // str_pad won't work properly with multi-byte strings, we need to fix the padding
        if (false !== $encoding = mb_detect_encoding($cell, null, true)) {
            $width += \strlen($cell) - mb_strwidth($cell, $encoding);
        }

        $style = $this->getColumnStyle($column);

        if ($cell instanceof TableSeparator) {
            return sprintf($style->getBorderFormat(), str_repeat($style->getHorizontalBorderChar(), $width));
        }

        $width += Helper::strlen($cell) - Helper::strlenWithoutDecoration($this->output->getFormatter(), $cell);
        $content = sprintf($style->getCellRowContentFormat(), $cell);

        return sprintf($cellFormat, str_pad($content, $width, $style->getPaddingChar(), $style->getPadType()));
    }

    /**
     * Calculate number of columns for this table.
     */
    private function calculateNumberOfColumns()
    {
        if (null !== $this->numberOfColumns) {
            return;
        }

        $columns = [0];
        foreach (array_merge($this->headers, $this->rows) as $row) {
            if ($row instanceof TableSeparator) {
                continue;
            }

            $columns[] = $this->getNumberOfColumns($row);
        }

        $this->numberOfColumns = max($columns);
    }

    private function buildTableRows($rows)
    {
        $unmergedRows = [];
        for ($rowKey = 0; $rowKey < \count($rows); ++$rowKey) {
            $rows = $this->fillNextRows($rows, $rowKey);

            // Remove any new line breaks and replace it with a new line
            foreach ($rows[$rowKey] as $column => $cell) {
                if (!strstr($cell ?? '', "\n")) {
                    continue;
                }
                $lines = explode("\n", str_replace("\n", "<fg=default;bg=default>\n</>", $cell));
                foreach ($lines as $lineKey => $line) {
                    if ($cell instanceof TableCell) {
                        $line = new TableCell($line, ['colspan' => $cell->getColspan()]);
                    }
                    if (0 === $lineKey) {
                        $rows[$rowKey][$column] = $line;
                    } else {
                        $unmergedRows[$rowKey][$lineKey][$column] = $line;
                    }
                }
            }
        }

        $tableRows = [];
        foreach ($rows as $rowKey => $row) {
            $tableRows[] = $this->fillCells($row);
            if (isset($unmergedRows[$rowKey])) {
                $tableRows = array_merge($tableRows, $unmergedRows[$rowKey]);
            }
        }

        return $tableRows;
    }

    /**
     * fill rows that contains rowspan > 1.
     *
     * @param int $line
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    private function fillNextRows(array $rows, $line)
    {
        $unmergedRows = [];
        foreach ($rows[$line] as $column => $cell) {
            if (null !== $cell && !$cell instanceof TableCell && !is_scalar($cell) && !(\is_object($cell) && method_exists($cell, '__toString'))) {
                throw new InvalidArgumentException(sprintf('A cell must be a TableCell, a scalar or an object implementing "__toString()", "%s" given.', \gettype($cell)));
            }
            if ($cell instanceof TableCell && $cell->getRowspan() > 1) {
                $nbLines = $cell->getRowspan() - 1;
                $lines = [$cell];
                if (strstr($cell, "\n")) {
                    $lines = explode("\n", str_replace("\n", "<fg=default;bg=default>\n</>", $cell));
                    $nbLines = \count($lines) > $nbLines ? substr_count($cell, "\n") : $nbLines;

                    $rows[$line][$column] = new TableCell($lines[0], ['colspan' => $cell->getColspan()]);
                    unset($lines[0]);
                }

                // create a two dimensional array (rowspan x colspan)
                $unmergedRows = array_replace_recursive(array_fill($line + 1, $nbLines, []), $unmergedRows);
                foreach ($unmergedRows as $unmergedRowKey => $unmergedRow) {
                    $value = isset($lines[$unmergedRowKey - $line]) ? $lines[$unmergedRowKey - $line] : '';
                    $unmergedRows[$unmergedRowKey][$column] = new TableCell($value, ['colspan' => $cell->getColspan()]);
                    if ($nbLines === $unmergedRowKey - $line) {
                        break;
                    }
                }
            }
        }

        foreach ($unmergedRows as $unmergedRowKey => $unmergedRow) {
            // we need to know if $unmergedRow will be merged or inserted into $rows
            if (isset($rows[$unmergedRowKey]) && \is_array($rows[$unmergedRowKey]) && ($this->getNumberOfColumns($rows[$unmergedRowKey]) + $this->getNumberOfColumns($unmergedRows[$unmergedRowKey]) <= $this->numberOfColumns)) {
                foreach ($unmergedRow as $cellKey => $cell) {
                    // insert cell into row at cellKey position
                    array_splice($rows[$unmergedRowKey], $cellKey, 0, [$cell]);
                }
            } else {
                $row = $this->copyRow($rows, $unmergedRowKey - 1);
                foreach ($unmergedRow as $column => $cell) {
                    if (!empty($cell)) {
                        $row[$column] = $unmergedRow[$column];
                    }
                }
                array_splice($rows, $unmergedRowKey, 0, [$row]);
            }
        }

        return $rows;
    }

    /**
     * fill cells for a row that contains colspan > 1.
     *
     * @return array
     */
    private function fillCells($row)
    {
        $newRow = [];
        foreach ($row as $column => $cell) {
            $newRow[] = $cell;
            if ($cell instanceof TableCell && $cell->getColspan() > 1) {
                foreach (range($column + 1, $column + $cell->getColspan() - 1) as $position) {
                    // insert empty value at column position
                    $newRow[] = '';
                }
            }
        }

        return $newRow ?: $row;
    }

    /**
     * @param int $line
     *
     * @return array
     */
    private function copyRow(array $rows, $line)
    {
        $row = $rows[$line];
        foreach ($row as $cellKey => $cellValue) {
            $row[$cellKey] = '';
            if ($cellValue instanceof TableCell) {
                $row[$cellKey] = new TableCell('', ['colspan' => $cellValue->getColspan()]);
            }
        }

        return $row;
    }

    /**
     * Gets number of columns by row.
     *
     * @return int
     */
    private function getNumberOfColumns(array $row)
    {
        $columns = \count($row);
        foreach ($row as $column) {
            $columns += $column instanceof TableCell ? ($column->getColspan() - 1) : 0;
        }

        return $columns;
    }

    /**
     * Gets list of columns for the given row.
     *
     * @return array
     */
    private function getRowColumns(array $row)
    {
        $columns = range(0, $this->numberOfColumns - 1);
        foreach ($row as $cellKey => $cell) {
            if ($cell instanceof TableCell && $cell->getColspan() > 1) {
                // exclude grouped columns.
                $columns = array_diff($columns, range($cellKey + 1, $cellKey + $cell->getColspan() - 1));
            }
        }

        return $columns;
    }

    /**
     * Calculates columns widths.
     */
    private function calculateColumnsWidth(array $rows)
    {
        for ($column = 0; $column < $this->numberOfColumns; ++$column) {
            $lengths = [];
            foreach ($rows as $row) {
                if ($row instanceof TableSeparator) {
                    continue;
                }

                foreach ($row as $i => $cell) {
                    if ($cell instanceof TableCell) {
                        $textContent = Helper::removeDecoration($this->output->getFormatter(), $cell);
                        $textLength = Helper::strlen($textContent);
                        if ($textLength > 0) {
                            $contentColumns = str_split($textContent, ceil($textLength / $cell->getColspan()));
                            foreach ($contentColumns as $position => $content) {
                                $row[$i + $position] = $content;
                            }
                        }
                    }
                }

                $lengths[] = $this->getCellWidth($row, $column);
            }

            $this->effectiveColumnWidths[$column] = max($lengths) + Helper::strlen($this->style->getCellRowContentFormat()) - 2;
        }
    }

    /**
     * Gets column width.
     *
     * @return int
     */
    private function getColumnSeparatorWidth()
    {
        return Helper::strlen(sprintf($this->style->getBorderFormat(), $this->style->getVerticalBorderChar()));
    }

    /**
     * Gets cell width.
     *
     * @param int $column
     *
     * @return int
     */
    private function getCellWidth(array $row, $column)
    {
        $cellWidth = 0;

        if (isset($row[$column])) {
            $cell = $row[$column];
            $cellWidth = Helper::strlenWithoutDecoration($this->output->getFormatter(), $cell);
        }

        $columnWidth = isset($this->columnWidths[$column]) ? $this->columnWidths[$column] : 0;

        return max($cellWidth, $columnWidth);
    }

    /**
     * Called after rendering to cleanup cache data.
     */
    private function cleanup()
    {
        $this->effectiveColumnWidths = [];
        $this->numberOfColumns = null;
    }

    private static function initStyles()
    {
        $borderless = new TableStyle();
        $borderless
            ->setHorizontalBorderChar('=')
            ->setVerticalBorderChar(' ')
            ->setCrossingChar(' ')
        ;

        $compact = new TableStyle();
        $compact
            ->setHorizontalBorderChar('')
            ->setVerticalBorderChar(' ')
            ->setCrossingChar('')
            ->setCellRowContentFormat('%s')
        ;

        $styleGuide = new TableStyle();
        $styleGuide
            ->setHorizontalBorderChar('-')
            ->setVerticalBorderChar(' ')
            ->setCrossingChar(' ')
            ->setCellHeaderFormat('%s')
        ;

        return [
            'default' => new TableStyle(),
            'borderless' => $borderless,
            'compact' => $compact,
            'symfony-style-guide' => $styleGuide,
        ];
    }

    private function resolveStyle($name)
    {
        if ($name instanceof TableStyle) {
            return $name;
        }

        if (isset(self::$styles[$name])) {
            return self::$styles[$name];
        }

        throw new InvalidArgumentException(sprintf('Style "%s" is not defined.', $name));
    }
}
