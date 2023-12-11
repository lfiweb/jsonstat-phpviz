<?php

namespace jsonstatPhpViz;

/**
 * Handles rendering of table cells.
 *
 * There are four types of cells to render:
 *
 * |---------------------------------------|
 * | header label cell | header value cell |
 * |-------------------|-------------------|
 * |     label cell    |     value cell    |
 * |-------------------|-------------------|
 *
 * e.g.:
 *
 * |---------------------------------------|
 * |    OECD country   |     year 2003     |
 * |-------------------|-------------------|
 * |       Sweden      |    6.56574156     |
 * |-------------------|-------------------|
 * |     Switzerland   |    4.033356027    |
 * |-------------------|-------------------|
 * |         ...       |         ...       |
 * |-------------------|-------------------|
 */
abstract class RendererCell
{
    protected Reader $reader;
    protected FormatterCell $formatter;

    /**
     * @param FormatterCell $cellFormatter
     * @param Reader $reader
     */
    public function __construct(FormatterCell $cellFormatter, Reader $reader)
    {
        $this->reader = $reader;
        $this->formatter = $cellFormatter;
    }
}