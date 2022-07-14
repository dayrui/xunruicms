/**
 * @preserve tableExport.jquery.plugin
 *
 * Version 1.21
 *
 * Copyright (c) 2015-2021 hhurz,
 *   https://github.com/hhurz/tableExport.jquery.plugin
 *
 * Based on https://github.com/kayalshri/tableExport.jquery.plugin
 *
 * Licensed under the MIT License
 **/

'use strict';

(function ($) {
  $.fn.tableExport = function (options) {
    let docData;
    const defaults = {
      csvEnclosure: '"',
      csvSeparator: ',',
      csvUseBOM: true,
      date: {
        html: 'dd/mm/yyyy'              // Date format used in html source. Supported placeholders: dd, mm, yy, yyyy and a arbitrary single separator character
      },                                
      displayTableName: false,          // Deprecated
      escape: false,                    // Deprecated
      exportHiddenCells: false,         // true = speed up export of large tables with hidden cells (hidden cells will be exported !)
      fileName: table_name,
      htmlContent: false,               
      htmlHyperlink: 'content',         // Export the 'content' or the 'href' link of <a> tags unless onCellHtmlHyperlink is not defined
      ignoreColumn: [0, table_ignoreColumn],
      ignoreRow: [$('#mytable > tbody > tr').length + 1],
      jsonScope: 'all',                 // One of 'head', 'data', 'all'
      jspdf: {                          // jsPDF / jsPDF-AutoTable related options
        orientation: 'p',               
        unit: 'pt',                     
        format: 'a4',                   // One of jsPDF page formats or 'bestfit' for automatic paper format selection
        margins: {left: 20, right: 10, top: 10, bottom: 10},
        onDocCreated: null,
        autotable: {
          styles: {
            cellPadding: 2,
            rowHeight: 12,
            fontSize: 8,
            fillColor: 255,             // Color value or 'inherit' to use css background-color from html table
            textColor: 50,              // Color value or 'inherit' to use css color from html table
            fontStyle: 'normal',        // 'normal', 'bold', 'italic', 'bolditalic' or 'inherit' to use css font-weight and font-style from html table
            overflow: 'ellipsize',      // 'visible', 'hidden', 'ellipsize' or 'linebreak'
            halign: 'inherit',          // 'left', 'center', 'right' or 'inherit' to use css horizontal cell alignment from html table
            valign: 'middle'            // 'top', 'middle', or 'bottom'
          },                          
          headerStyles: {             
            fillColor: [52, 73, 94],  
            textColor: 255,           
            fontStyle: 'bold',        
            halign: 'inherit',          // 'left', 'center', 'right' or 'inherit' to use css horizontal header cell alignment from html table
            valign: 'middle'            // 'top', 'middle', or 'bottom'
          },                          
          alternateRowStyles: {       
            fillColor: 245            
          },                          
          tableExport: {              
            doc: null,                  // jsPDF doc object. If set, an already created doc object will be used to export to
            onAfterAutotable: null,
            onBeforeAutotable: null,
            onAutotableText: null,
            onTable: null,
            outputImages: true
          }
        }
      },
      mso: {                            // MS Excel and MS Word related options
        fileFormat: 'xlshtml',          // 'xlshtml' = Excel 2000 html format
                                        // 'xmlss' = XML Spreadsheet 2003 file format (XMLSS)
                                        // 'xlsx' = Excel 2007 Office Open XML format
        onMsoNumberFormat: function(cell, row, col) {
          return '\\@';
        },        // Excel 2000 html format only. See readme.md for more information about msonumberformat
        pageFormat: 'a4',               // Page format used for page orientation
        pageOrientation: 'portrait',    // portrait, landscape (xlshtml format only)
        rtl: false,                     // true = Set worksheet option 'DisplayRightToLeft'
        styles: [],                     // E.g. ['border-bottom', 'border-top', 'border-left', 'border-right']
        worksheetName: '',
        xlsx: {                         // Specific Excel 2007 XML format settings:
          formatId: {                   // XLSX format (id) used to format excel cells. See readme.md: data-tableexport-xlsxformatid
            date: 14,                   // formatId or format string (e.g. 'm/d/yy') or function(cell, row, col) {return formatId}
            numbers: 2                  // formatId or format string (e.g. '\"T\"\ #0.00') or function(cell, row, col) {return formatId}
          },
          onHyperlink: null             // function($cell, row, col, href, content, hyperlink): Return what to export for hyperlinks
        }
      },
      numbers: {
        html: {
          decimalMark: '.',             // Decimal mark in html source
          thousandsSeparator: ','       // Thousands separator in html source
        },
        output: {                       // Set 'output: false' to keep number format of html source in resulting output
          decimalMark: '.',             // Decimal mark in resulting output
          thousandsSeparator: ','       // Thousands separator in resulting output
        }
      },
      onAfterSaveToFile: null,          // function(data, fileName)
      onBeforeSaveToFile: null,         // saveIt = function(data, fileName, type, charset, encoding): Return false to abort save process
      onCellData: null,                 // Text to export = function($cell, row, col, href, cellText, cellType)
      onCellHtmlData: null,             // Text to export = function($cell, row, col, htmlContent)
      onCellHtmlHyperlink: null,        // Text to export = function($cell, row, col, href, cellText)
      onIgnoreRow: null,                // ignoreRow = function($tr, row): Return true to prevent export of the row
      onTableExportBegin: null,         // function() - called when export starts
      onTableExportEnd: null,           // function() - called when export ends
      outputMode: 'file',               // 'file', 'string', 'base64' or 'window' (experimental)
      pdfmake: {
        enabled: true,                 // true: Use pdfmake as pdf producer instead of jspdf and jspdf-autotable
        docDefinition: {
          pageSize: 'A4',               // 4A0,2A0,A{0-10},B{0-10},C{0-10},RA{0-4},SRA{0-4},EXECUTIVE,FOLIO,LEGAL,LETTER,TABLOID
          pageOrientation: 'landscape',  // 'portrait' or 'landscape'
          styles: {
            header: {
              background: '#34495E',
              color: '#FFFFFF',
              bold: true,
              alignment: 'center',
              fillColor: '#34495E'
            },
            alternateRow: {
              fillColor: '#f5f5f5'
            }
          },
          defaultStyle: {
            color: '#000000',
            fontSize: 8,
            font: 'Roboto'              // Default font is 'Roboto' which needs vfs_fonts.js to be included
          }                             // To export arabic characters include mirza_fonts.js _instead_ of vfs_fonts.js
        },                              // For a chinese font include either gbsn00lp_fonts.js or ZCOOLXiaoWei_fonts.js _instead_ of vfs_fonts.js
        fonts: {}
      },
      preserve: {
        leadingWS: false,               // preserve leading white spaces
        trailingWS: false               // preserve trailing white spaces
      },
      preventInjection: true,           // Prepend a single quote to cell strings that start with =,+,- or @ to prevent formula injection
      sql: {
        tableEnclosure: '`',            // If table name or column names contain any characters except letters, numbers, and
        columnEnclosure: '`'            // underscores, usually the name must be delimited by enclosing it in back quotes (`)
      },
      tbodySelector: 'tr',
      tfootSelector: 'tr',              // Set empty ('') to prevent export of tfoot rows
      theadSelector: 'tr',
      tableName: 'Table',
      type: 'csv'                       // Export format: 'csv', 'tsv', 'txt', 'sql', 'json', 'xml', 'excel', 'doc', 'png' or 'pdf'
    };

    const pageFormats = { // Size in pt of various paper formats. Adopted from jsPDF.
      'a0': [2383.94, 3370.39], 'a1': [1683.78, 2383.94], 'a2': [1190.55, 1683.78],
      'a3': [841.89, 1190.55], 'a4': [595.28, 841.89], 'a5': [419.53, 595.28],
      'a6': [297.64, 419.53], 'a7': [209.76, 297.64], 'a8': [147.40, 209.76],
      'a9': [104.88, 147.40], 'a10': [73.70, 104.88],
      'b0': [2834.65, 4008.19], 'b1': [2004.09, 2834.65], 'b2': [1417.32, 2004.09],
      'b3': [1000.63, 1417.32], 'b4': [708.66, 1000.63], 'b5': [498.90, 708.66],
      'b6': [354.33, 498.90], 'b7': [249.45, 354.33], 'b8': [175.75, 249.45],
      'b9': [124.72, 175.75], 'b10': [87.87, 124.72],
      'c0': [2599.37, 3676.54],
      'c1': [1836.85, 2599.37], 'c2': [1298.27, 1836.85], 'c3': [918.43, 1298.27],
      'c4': [649.13, 918.43], 'c5': [459.21, 649.13], 'c6': [323.15, 459.21],
      'c7': [229.61, 323.15], 'c8': [161.57, 229.61], 'c9': [113.39, 161.57],
      'c10': [79.37, 113.39],
      'dl': [311.81, 623.62],
      'letter': [612, 792], 'government-letter': [576, 756], 'legal': [612, 1008],
      'junior-legal': [576, 360], 'ledger': [1224, 792], 'tabloid': [792, 1224],
      'credit-card': [153, 243]
    };

    const jsPdfThemes = { // Styles for the themes
      'striped': {
        table: {
          fillColor: 255,
          textColor: 80,
          fontStyle: 'normal',
          fillStyle: 'F'
        },
        header: {
          textColor: 255,
          fillColor: [41, 128, 185],
          rowHeight: 23,
          fontStyle: 'bold'
        },
        body: {},
        alternateRow: {fillColor: 245}
      },
      'grid': {
        table: {
          fillColor: 255,
          textColor: 80,
          fontStyle: 'normal',
          lineWidth: 0.1,
          fillStyle: 'DF'
        },
        header: {
          textColor: 255,
          fillColor: [26, 188, 156],
          rowHeight: 23,
          fillStyle: 'F',
          fontStyle: 'bold'
        },
        body: {},
        alternateRow: {}
      },
      'plain': {header: {fontStyle: 'bold'}}
    };

    const jsPdfDefaultStyles = { // Base style for all themes
      cellPadding: 5,
      fontSize: 10,
      font: "helvetica",         // helvetica, times, courier
      lineColor: 200,
      lineWidth: 0.1,
      fontStyle: 'normal',       // normal, bold, italic, bolditalic
      overflow: 'ellipsize',     // visible, hidden, ellipsize or linebreak
      fillColor: 255,
      textColor: 20,
      halign: 'left',            // left, center, right
      valign: 'top',             // top, middle, bottom
      fillStyle: 'F',            // 'S', 'F' or 'DF' (stroke, fill or fill then stroke)
      rowHeight: 20,
      columnWidth: 'auto'
    };

    const FONT_ROW_RATIO = 1.15;
    const el = this;
    let DownloadEvt = null;
    let $head_rows = [];
    let $rows = [];
    let rowIndex = 0;
    let trData = '';
    let colNames = [];
    let ranges = [];
    let blob;
    let $hiddenTableElements = [];
    let checkCellVisibility = false;

    $.extend(true, defaults, options);

    // Adopt deprecated options
    if (defaults.type === 'xlsx') {
      defaults.mso.fileFormat = defaults.type;
      defaults.type = 'excel';
    }
    if (typeof defaults.excelFileFormat !== 'undefined' && typeof defaults.mso.fileFormat === 'undefined')
      defaults.mso.fileFormat = defaults.excelFileFormat;
    if (typeof defaults.excelPageFormat !== 'undefined' && typeof defaults.mso.pageFormat === 'undefined')
      defaults.mso.pageFormat = defaults.excelPageFormat;
    if (typeof defaults.excelPageOrientation !== 'undefined' && typeof defaults.mso.pageOrientation === 'undefined')
      defaults.mso.pageOrientation = defaults.excelPageOrientation;
    if (typeof defaults.excelRTL !== 'undefined' && typeof defaults.mso.rtl === 'undefined')
      defaults.mso.rtl = defaults.excelRTL;
    if (typeof defaults.excelstyles !== 'undefined' && typeof defaults.mso.styles === 'undefined')
      defaults.mso.styles = defaults.excelstyles;
    if (typeof defaults.onMsoNumberFormat !== 'undefined' && typeof defaults.mso.onMsoNumberFormat === 'undefined')
      defaults.mso.onMsoNumberFormat = defaults.onMsoNumberFormat;
    if (typeof defaults.worksheetName !== 'undefined' && typeof defaults.mso.worksheetName === 'undefined')
      defaults.mso.worksheetName = defaults.worksheetName;
    if (typeof defaults.mso.xslx !== 'undefined' && typeof defaults.mso.xlsx === 'undefined')
      defaults.mso.xlsx = defaults.mso.xslx;

    // Check values of some options
    defaults.mso.pageOrientation = (defaults.mso.pageOrientation.substr(0, 1) === 'l') ? 'landscape' : 'portrait';
    defaults.date.html = defaults.date.html || '';

    if (defaults.date.html.length) {
      const patt = [];
      patt['dd'] = '(3[01]|[12][0-9]|0?[1-9])';
      patt['mm'] = '(1[012]|0?[1-9])';
      patt['yyyy'] = '((?:1[6-9]|2[0-2])\\d{2})';
      patt['yy'] = '(\\d{2})';

      const separator = defaults.date.html.match(/[^a-zA-Z0-9]/)[0];
      const formatItems = defaults.date.html.toLowerCase().split(separator);
      defaults.date.regex = '^\\s*';
      defaults.date.regex += patt[formatItems[0]];
      defaults.date.regex += '(.)'; // separator group
      defaults.date.regex += patt[formatItems[1]];
      defaults.date.regex += '\\2'; // identical separator group
      defaults.date.regex += patt[formatItems[2]];
      defaults.date.regex += '\\s*$';
      // e.g. '^\\s*(3[01]|[12][0-9]|0?[1-9])(.)(1[012]|0?[1-9])\\2((?:1[6-9]|2[0-2])\\d{2})\\s*$'

      defaults.date.pattern = new RegExp(defaults.date.regex, 'g');
      let f = formatItems.indexOf('dd') + 1;
      defaults.date.match_d = f + (f > 1 ? 1 : 0);
      f = formatItems.indexOf('mm') + 1;
      defaults.date.match_m = f + (f > 1 ? 1 : 0);
      f = (formatItems.indexOf('yyyy') >= 0 ? formatItems.indexOf('yyyy') : formatItems.indexOf('yy')) + 1;
      defaults.date.match_y = f + (f > 1 ? 1 : 0);
    }

    colNames = GetColumnNames(el);

    if (typeof defaults.onTableExportBegin === 'function')
      defaults.onTableExportBegin();

    if (defaults.type === 'csv' || defaults.type === 'tsv' || defaults.type === 'txt') {

      let csvData = '';
      let rowLength = 0;
      ranges = [];
      rowIndex = 0;

      const csvString = function (cell, rowIndex, colIndex) {
        let result = '';

        if (cell !== null) {
          const dataString = parseString(cell, rowIndex, colIndex);

          const csvValue = (dataString === null || dataString === '') ? '' : dataString.toString();

          if (defaults.type === 'tsv') {
            if (dataString instanceof Date)
              dataString.toLocaleString();

            // According to http://www.iana.org/assignments/media-types/text/tab-separated-values
            // are fields that contain tabs not allowable in tsv encoding
            result = replaceAll(csvValue, '\t', ' ');
          } else {
            // Takes a string and encapsulates it (by default in double-quotes) if it
            // contains the csv field separator, spaces, or linebreaks.
            if (dataString instanceof Date)
              result = defaults.csvEnclosure + dataString.toLocaleString() + defaults.csvEnclosure;
            else {
              result = preventInjection(csvValue);
              result = replaceAll(result, defaults.csvEnclosure, defaults.csvEnclosure + defaults.csvEnclosure);

              if (result.indexOf(defaults.csvSeparator) >= 0 || /[\r\n ]/g.test(result))
                result = defaults.csvEnclosure + result + defaults.csvEnclosure;
            }
          }
        }

        return result;
      };

      const CollectCsvData = function ($rows, rowselector, length) {

        $rows.each(function () {
          trData = '';
          ForEachVisibleCell(this, rowselector, rowIndex, length + $rows.length,
              function (cell, row, col) {
                trData += csvString(cell, row, col) + (defaults.type === 'tsv' ? '\t' : defaults.csvSeparator);
              });
          trData = $.trim(trData).substring(0, trData.length - 1);
          if (trData.length > 0) {

            if (csvData.length > 0)
              csvData += '\n';

            csvData += trData;
          }
          rowIndex++;
        });

        return $rows.length;
      };

      rowLength += CollectCsvData($(el).find('thead').first().find(defaults.theadSelector), 'th,td', rowLength);
      findTableElements($(el), 'tbody').each(function () {
        rowLength += CollectCsvData(findTableElements($(this), defaults.tbodySelector), 'td,th', rowLength);
      });
      if (defaults.tfootSelector.length)
        CollectCsvData($(el).find('tfoot').first().find(defaults.tfootSelector), 'td,th', rowLength);

      csvData += '\n';

      //output
      if (defaults.outputMode === 'string')
        return csvData;

      if (defaults.outputMode === 'base64')
        return base64encode(csvData);

      if (defaults.outputMode === 'window') {
        downloadFile(false, 'data:text/' + (defaults.type === 'csv' ? 'csv' : 'plain') + ';charset=utf-8,', csvData);
        return;
      }

      saveToFile(csvData,
        defaults.fileName + '.' + defaults.type,
        'text/' + (defaults.type === 'csv' ? 'csv' : 'plain'),
        'utf-8',
        '',
        (defaults.type === 'csv' && defaults.csvUseBOM));

    } else if (defaults.type === 'sql') {

      // Header
      rowIndex = 0;
      ranges = [];
      let tdData = 'INSERT INTO ' + defaults.sql.tableEnclosure + defaults.tableName + defaults.sql.tableEnclosure + ' (';
      $head_rows = collectHeadRows($(el));
      $($head_rows).each(function () {
        ForEachVisibleCell(this, 'th,td', rowIndex, $head_rows.length,
          function (cell, row, col) {
            let colName = parseString(cell, row, col) || '';
            if (colName.indexOf(defaults.sql.columnEnclosure) > -1)
              colName = replaceAll(colName.toString(), defaults.sql.columnEnclosure, defaults.sql.columnEnclosure + defaults.sql.columnEnclosure);
            tdData += defaults.sql.columnEnclosure + colName + defaults.sql.columnEnclosure + ',';
          });
        rowIndex++;
        tdData = $.trim(tdData).substring(0, tdData.length - 1);
      });
      tdData += ') VALUES ';

      // Data
      $rows = collectRows($(el));
      $($rows).each(function () {
        trData = '';
        ForEachVisibleCell(this, 'td,th', rowIndex, $head_rows.length + $rows.length,
          function (cell, row, col) {
            let dataString = parseString(cell, row, col) || '';
            if (dataString.indexOf('\'') > -1)
              dataString = replaceAll(dataString.toString(), '\'', '\'\'');
            trData += '\'' + dataString + '\',';
          });
        if (trData.length > 3) {
          tdData += '(' + trData;
          tdData = $.trim(tdData).substring(0, tdData.length - 1);
          tdData += '),';
        }
        rowIndex++;
      });

      tdData = $.trim(tdData).substring(0, tdData.length - 1);
      tdData += ';';

      // Output
      if (defaults.outputMode === 'string')
        return tdData;

      if (defaults.outputMode === 'base64')
        return base64encode(tdData);

      saveToFile(tdData, defaults.fileName + '.sql', 'application/sql', 'utf-8', '', false);

    } else if (defaults.type === 'json') {
      const jsonHeaderArray = [];
      ranges = [];
      $head_rows = collectHeadRows($(el));
      $($head_rows).each(function () {
        const jsonArrayTd = [];

        ForEachVisibleCell(this, 'th,td', rowIndex, $head_rows.length,
          function (cell, row, col) {
            jsonArrayTd.push(parseString(cell, row, col));
          });
        jsonHeaderArray.push(jsonArrayTd);
      });

      // Data
      const jsonArray = [];

      $rows = collectRows($(el));
      $($rows).each(function () {
        const jsonObjectTd = {};
        let colIndex = 0;

        ForEachVisibleCell(this, 'td,th', rowIndex, $head_rows.length + $rows.length,
          function (cell, row, col) {
            if (jsonHeaderArray.length) {
              jsonObjectTd[jsonHeaderArray[jsonHeaderArray.length - 1][colIndex]] = parseString(cell, row, col);
            } else {
              jsonObjectTd[colIndex] = parseString(cell, row, col);
            }
            colIndex++;
          });
        if ($.isEmptyObject(jsonObjectTd) === false)
          jsonArray.push(jsonObjectTd);

        rowIndex++;
      });

      let save_data;

      if (defaults.jsonScope === 'head')
        save_data = JSON.stringify(jsonHeaderArray);
      else if (defaults.jsonScope === 'data')
        save_data = JSON.stringify(jsonArray);
      else // all
        save_data = JSON.stringify({header: jsonHeaderArray, data: jsonArray});

      if (defaults.outputMode === 'string')
        return save_data;

      if (defaults.outputMode === 'base64')
        return base64encode(save_data);

      saveToFile(save_data, defaults.fileName + '.json', 'application/json', 'utf-8', 'base64', false);

    } else if (defaults.type === 'xml') {
      rowIndex = 0;
      ranges = [];
      let xml = '<?xml version="1.0" encoding="utf-8"?>';
      xml += '<tabledata><fields>';

      // Header
      $head_rows = collectHeadRows($(el));
      $($head_rows).each(function () {

        ForEachVisibleCell(this, 'th,td', rowIndex, $head_rows.length,
          function (cell, row, col) {
            xml += '<field>' + parseString(cell, row, col) + '</field>';
          });
        rowIndex++;
      });
      xml += '</fields><data>';

      // Data
      let rowCount = 1;

      $rows = collectRows($(el));
      $($rows).each(function () {
        let colCount = 1;
        trData = '';
        ForEachVisibleCell(this, 'td,th', rowIndex, $head_rows.length + $rows.length,
          function (cell, row, col) {
            trData += '<column-' + colCount + '>' + parseString(cell, row, col) + '</column-' + colCount + '>';
            colCount++;
          });
        if (trData.length > 0 && trData !== '<column-1></column-1>') {
          xml += '<row id="' + rowCount + '">' + trData + '</row>';
          rowCount++;
        }

        rowIndex++;
      });
      xml += '</data></tabledata>';

      // Output
      if (defaults.outputMode === 'string')
        return xml;

      if (defaults.outputMode === 'base64')
        return base64encode(xml);

      saveToFile(xml, defaults.fileName + '.xml', 'application/xml', 'utf-8', 'base64', false);
    } else if (defaults.type === 'excel' && defaults.mso.fileFormat === 'xmlss') {
      const sheetData = [];
      const docNames = [];

      $(el).filter(function () {
        return isVisible($(this));
      }).each(function () {
        const $table = $(this);

        let ssName = '';
        if (typeof defaults.mso.worksheetName === 'string' && defaults.mso.worksheetName.length)
          ssName = defaults.mso.worksheetName + ' ' + (docNames.length + 1);
        else if (typeof defaults.mso.worksheetName[docNames.length] !== 'undefined')
          ssName = defaults.mso.worksheetName[docNames.length];
        if (!ssName.length)
          ssName = $table.find('caption').text() || '';
        if (!ssName.length)
          ssName = 'Table ' + (docNames.length + 1);
        ssName = $.trim(ssName.replace(/[\\\/[\]*:?'"]/g, '').substring(0, 31));

        docNames.push($('<div />').text(ssName).html());

        if (defaults.exportHiddenCells === false) {
          $hiddenTableElements = $table.find('tr, th, td').filter(':hidden');
          checkCellVisibility = $hiddenTableElements.length > 0;
        }

        rowIndex = 0;
        colNames = GetColumnNames(this);
        docData = '<Table>\r';

        function CollectXmlssData ($rows, rowselector, length) {
          const spans = [];

          $($rows).each(function () {
            let ssIndex = 0;
            let nCols = 0;
            trData = '';

            ForEachVisibleCell(this, 'td,th', rowIndex, length + $rows.length,
              function (cell, row, col) {
                if (cell !== null) {
                  let style = '';
                  let data = parseString(cell, row, col);
                  let type = 'String';

                  if (jQuery.isNumeric(data) !== false) {
                    type = 'Number';
                  } else {
                    const number = parsePercent(data);
                    if (number !== false) {
                      data = number;
                      type = 'Number';
                      style += ' ss:StyleID="pct1"';
                    }
                  }

                  if (type !== 'Number')
                    data = data.replace(/\n/g, '<br>');

                  let colspan = getColspan(cell);
                  let rowspan = getRowspan(cell);

                  // Skip spans
                  $.each(spans, function () {
                    const range = this;
                    if (rowIndex >= range.s.r && rowIndex <= range.e.r && nCols >= range.s.c && nCols <= range.e.c) {
                      for (let i = 0; i <= range.e.c - range.s.c; ++i) {
                        nCols++;
                        ssIndex++;
                      }
                    }
                  });

                  // Handle Row Span
                  if (rowspan || colspan) {
                    rowspan = rowspan || 1;
                    colspan = colspan || 1;
                    spans.push({
                      s: {r: rowIndex, c: nCols},
                      e: {r: rowIndex + rowspan - 1, c: nCols + colspan - 1}
                    });
                  }

                  // Handle Colspan
                  if (colspan > 1) {
                    style += ' ss:MergeAcross="' + (colspan - 1) + '"';
                    nCols += (colspan - 1);
                  }

                  if (rowspan > 1) {
                    style += ' ss:MergeDown="' + (rowspan - 1) + '" ss:StyleID="rsp1"';
                  }

                  if (ssIndex > 0) {
                    style += ' ss:Index="' + (nCols + 1) + '"';
                    ssIndex = 0;
                  }

                  trData += '<Cell' + style + '><Data ss:Type="' + type + '">' +
                    $('<div />').text(data).html() +
                    '</Data></Cell>\r';
                  nCols++;
                }
              });
            if (trData.length > 0)
              docData += '<Row ss:AutoFitHeight="0">\r' + trData + '</Row>\r';
            rowIndex++;
          });

          return $rows.length;
        }

        const rowLength = CollectXmlssData(collectHeadRows($table), 'th,td', 0);
        CollectXmlssData(collectRows($table), 'td,th', rowLength);

        docData += '</Table>\r';
        sheetData.push(docData);
      });

      const count = {};
      const firstOccurrences = {};
      let item, itemCount;
      for (let n = 0, c = docNames.length; n < c; n++) {
        item = docNames[n];
        itemCount = count[item];
        itemCount = count[item] = (itemCount == null ? 1 : itemCount + 1);

        if (itemCount === 2)
          docNames[firstOccurrences[item]] = docNames[firstOccurrences[item]].substring(0, 29) + '-1';
        if (count[item] > 1)
          docNames[n] = docNames[n].substring(0, 29) + '-' + count[item];
        else
          firstOccurrences[item] = n;
      }

      const CreationDate = new Date().toISOString();
      let xmlssDocFile = '<?xml version="1.0" encoding="UTF-8"?>\r' +
          '<?mso-application progid="Excel.Sheet"?>\r' +
          '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"\r' +
          ' xmlns:o="urn:schemas-microsoft-com:office:office"\r' +
          ' xmlns:x="urn:schemas-microsoft-com:office:excel"\r' +
          ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"\r' +
          ' xmlns:html="http://www.w3.org/TR/REC-html40">\r' +
          '<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">\r' +
          '  <Created>' + CreationDate + '</Created>\r' +
          '</DocumentProperties>\r' +
          '<OfficeDocumentSettings xmlns="urn:schemas-microsoft-com:office:office">\r' +
          '  <AllowPNG/>\r' +
          '</OfficeDocumentSettings>\r' +
          '<ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">\r' +
          '  <WindowHeight>9000</WindowHeight>\r' +
          '  <WindowWidth>13860</WindowWidth>\r' +
          '  <WindowTopX>0</WindowTopX>\r' +
          '  <WindowTopY>0</WindowTopY>\r' +
          '  <ProtectStructure>False</ProtectStructure>\r' +
          '  <ProtectWindows>False</ProtectWindows>\r' +
          '</ExcelWorkbook>\r' +
          '<Styles>\r' +
          '  <Style ss:ID="Default" ss:Name="Normal">\r' +
          '    <Alignment ss:Vertical="Bottom"/>\r' +
          '    <Borders/>\r' +
          '    <Font/>\r' +
          '    <Interior/>\r' +
          '    <NumberFormat/>\r' +
          '    <Protection/>\r' +
          '  </Style>\r' +
          '  <Style ss:ID="rsp1">\r' +
          '    <Alignment ss:Vertical="Center"/>\r' +
          '  </Style>\r' +
          '  <Style ss:ID="pct1">\r' +
          '    <NumberFormat ss:Format="Percent"/>\r' +
          '  </Style>\r' +
          '</Styles>\r';

      for (let j = 0; j < sheetData.length; j++) {
        xmlssDocFile += '<Worksheet ss:Name="' + docNames[j] + '" ss:RightToLeft="' + (defaults.mso.rtl ? '1' : '0') + '">\r' +
          sheetData[j];
        if (defaults.mso.rtl) {
          xmlssDocFile += '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">\r' +
            '<DisplayRightToLeft/>\r' +
            '</WorksheetOptions>\r';
        } else
          xmlssDocFile += '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"/>\r';
        xmlssDocFile += '</Worksheet>\r';
      }

      xmlssDocFile += '</Workbook>\r';

      if (defaults.outputMode === 'string')
        return xmlssDocFile;

      if (defaults.outputMode === 'base64')
        return base64encode(xmlssDocFile);

      saveToFile(xmlssDocFile, defaults.fileName + '.xml', 'application/xml', 'utf-8', 'base64', false);
    } else if (defaults.type === 'excel' && defaults.mso.fileFormat === 'xlsx') {

      const sheetNames = [];
      const workbook = XLSX.utils.book_new();

      // Multiple worksheets and .xlsx file extension #202

      $(el).filter(function () {
        return isVisible($(this));
      }).each(function () {
        const $table = $(this);
        const ws = xlsxTableToSheet(this);

        let sheetName = '';
        if (typeof defaults.mso.worksheetName === 'string' && defaults.mso.worksheetName.length)
          sheetName = defaults.mso.worksheetName + ' ' + (sheetNames.length + 1);
        else if (typeof defaults.mso.worksheetName[sheetNames.length] !== 'undefined')
          sheetName = defaults.mso.worksheetName[sheetNames.length];
        if (!sheetName.length)
          sheetName = $table.find('caption').text() || '';
        if (!sheetName.length)
          sheetName = 'Table ' + (sheetNames.length + 1);
        sheetName = $.trim(sheetName.replace(/[\\\/[\]*:?'"]/g, '').substring(0, 31));

        sheetNames.push(sheetName);
        XLSX.utils.book_append_sheet(workbook, ws, sheetName);
      });

      // add worksheet to workbook
      const wbData = XLSX.write(workbook, {type: 'binary', bookType: defaults.mso.fileFormat, bookSST: false});

      saveToFile(xlsxWorkbookToArrayBuffer(wbData),
        defaults.fileName + '.' + defaults.mso.fileFormat,
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'UTF-8', '', false);
    } else if (defaults.type === 'excel' || defaults.type === 'xls' || defaults.type === 'word' || defaults.type === 'doc') {

      const MSDocType = (defaults.type === 'excel' || defaults.type === 'xls') ? 'excel' : 'word';
      const MSDocExt = (MSDocType === 'excel') ? 'xls' : 'doc';
      const MSDocSchema = 'xmlns:x="urn:schemas-microsoft-com:office:' + MSDocType + '"';
      docData = '';
      let docName = '';

      $(el).filter(function () {
        return isVisible($(this));
      }).each(function () {
        const $table = $(this);

        if (docName === '') {
          docName = defaults.mso.worksheetName || $table.find('caption').text() || 'Table';
          docName = $.trim(docName.replace(/[\\\/[\]*:?'"]/g, '').substring(0, 31));
        }

        if (defaults.exportHiddenCells === false) {
          $hiddenTableElements = $table.find('tr, th, td').filter(':hidden');
          checkCellVisibility = $hiddenTableElements.length > 0;
        }

        rowIndex = 0;
        ranges = [];
        colNames = GetColumnNames(this);

        // Header
        docData += '<table><thead>';
        $head_rows = collectHeadRows($table);
        $($head_rows).each(function () {
          const $row = $(this);
          trData = '';
          ForEachVisibleCell(this, 'th,td', rowIndex, $head_rows.length,
            function (cell, row, col) {
              if (cell !== null) {
                let thStyle = '';

                trData += '<th';
                if (defaults.mso.styles.length) {
                  const cellStyles = document.defaultView.getComputedStyle(cell, null);
                  const rowStyles = document.defaultView.getComputedStyle($row[0], null);

                  for (let cssStyle in defaults.mso.styles) {
                    let thCss = cellStyles[defaults.mso.styles[cssStyle]];
                    if (thCss === '')
                      thCss = rowStyles[defaults.mso.styles[cssStyle]];
                    if (thCss !== '' && thCss !== '0px none rgb(0, 0, 0)' && thCss !== 'rgba(0, 0, 0, 0)') {
                      thStyle += (thStyle === '') ? 'style="' : ';';
                      thStyle += defaults.mso.styles[cssStyle] + ':' + thCss;
                    }
                  }
                }
                if (thStyle !== '')
                  trData += ' ' + thStyle + '"';

                const tdColspan = getColspan(cell);
                if (tdColspan > 0)
                  trData += ' colspan="' + tdColspan + '"';

                const tdRowspan = getRowspan(cell);
                if (tdRowspan > 0)
                  trData += ' rowspan="' + tdRowspan + '"';

                trData += '>' + parseString(cell, row, col) + '</th>';
              }
            });
          if (trData.length > 0)
            docData += '<tr>' + trData + '</tr>';
          rowIndex++;
        });
        docData += '</thead><tbody>';

        // Data
        $rows = collectRows($table);
        $($rows).each(function () {
          const $row = $(this);
          trData = '';
          ForEachVisibleCell(this, 'td,th', rowIndex, $head_rows.length + $rows.length,
            function (cell, row, col) {
              if (cell !== null) {
                let tdValue = parseString(cell, row, col);
                let tdStyle = '';
                let tdCss = $(cell).attr('data-tableexport-msonumberformat');

                if (typeof tdCss === 'undefined' && typeof defaults.mso.onMsoNumberFormat === 'function')
                  tdCss = defaults.mso.onMsoNumberFormat(cell, row, col);

                if (typeof tdCss !== 'undefined' && tdCss !== '')
                  tdStyle = 'style="mso-number-format:\'' + tdCss + '\'';

                if (defaults.mso.styles.length) {
                  const cellStyles = document.defaultView.getComputedStyle(cell, null);
                  const rowStyles = document.defaultView.getComputedStyle($row[0], null);

                  for (let cssStyle in defaults.mso.styles) {
                    tdCss = cellStyles[defaults.mso.styles[cssStyle]];
                    if (tdCss === '')
                      tdCss = rowStyles[defaults.mso.styles[cssStyle]];

                    if (tdCss !== '' && tdCss !== '0px none rgb(0, 0, 0)' && tdCss !== 'rgba(0, 0, 0, 0)') {
                      tdStyle += (tdStyle === '') ? 'style="' : ';';
                      tdStyle += defaults.mso.styles[cssStyle] + ':' + tdCss;
                    }
                  }
                }

                trData += '<td';
                if (tdStyle !== '')
                  trData += ' ' + tdStyle + '"';

                const tdColspan = getColspan(cell);
                if (tdColspan > 0)
                  trData += ' colspan="' + tdColspan + '"';

                const tdRowspan = getRowspan(cell);
                if (tdRowspan > 0)
                  trData += ' rowspan="' + tdRowspan + '"';

                if (typeof tdValue === 'string' && tdValue !== '') {
                  tdValue = preventInjection(tdValue);
                  tdValue = tdValue.replace(/\n/g, '<br>');
                }

                trData += '>' + tdValue + '</td>';
              }
            });
          if (trData.length > 0)
            docData += '<tr>' + trData + '</tr>';
          rowIndex++;
        });

        if (defaults.displayTableName)
          docData += '<tr><td></td></tr><tr><td></td></tr><tr><td>' + parseString($('<p>' + defaults.tableName + '</p>')) + '</td></tr>';

        docData += '</tbody></table>';
      });

      //noinspection XmlUnusedNamespaceDeclaration
      let docFile = '<html xmlns:o="urn:schemas-microsoft-com:office:office" ' + MSDocSchema + ' xmlns="http://www.w3.org/TR/REC-html40">';
      docFile += '<meta http-equiv="content-type" content="application/vnd.ms-' + MSDocType + '; charset=UTF-8">';
      docFile += '<head>';
      if (MSDocType === 'excel') {
        docFile += '<!--[if gte mso 9]>';
        docFile += '<xml>';
        docFile += '<x:ExcelWorkbook>';
        docFile += '<x:ExcelWorksheets>';
        docFile += '<x:ExcelWorksheet>';
        docFile += '<x:Name>';
        docFile += docName;
        docFile += '</x:Name>';
        docFile += '<x:WorksheetOptions>';
        docFile += '<x:DisplayGridlines/>';
        if (defaults.mso.rtl)
          docFile += '<x:DisplayRightToLeft/>';
        docFile += '</x:WorksheetOptions>';
        docFile += '</x:ExcelWorksheet>';
        docFile += '</x:ExcelWorksheets>';
        docFile += '</x:ExcelWorkbook>';
        docFile += '</xml>';
        docFile += '<![endif]-->';
      }
      docFile += '<style>';

      docFile += '@page { size:' + defaults.mso.pageOrientation + '; mso-page-orientation:' + defaults.mso.pageOrientation + '; }';
      docFile += '@page Section1 {size:' + pageFormats[defaults.mso.pageFormat][0] + 'pt ' + pageFormats[defaults.mso.pageFormat][1] + 'pt';
      docFile += '; margin:1.0in 1.25in 1.0in 1.25in;mso-header-margin:.5in;mso-footer-margin:.5in;mso-paper-source:0;}';
      docFile += 'div.Section1 {page:Section1;}';
      docFile += '@page Section2 {size:' + pageFormats[defaults.mso.pageFormat][1] + 'pt ' + pageFormats[defaults.mso.pageFormat][0] + 'pt';
      docFile += ';mso-page-orientation:' + defaults.mso.pageOrientation + ';margin:1.25in 1.0in 1.25in 1.0in;mso-header-margin:.5in;mso-footer-margin:.5in;mso-paper-source:0;}';
      docFile += 'div.Section2 {page:Section2;}';

      docFile += 'br {mso-data-placement:same-cell;}';
      docFile += '</style>';
      docFile += '</head>';
      docFile += '<body>';
      docFile += '<div class="Section' + ((defaults.mso.pageOrientation === 'landscape') ? '2' : '1') + '">';
      docFile += docData;
      docFile += '</div>';
      docFile += '</body>';
      docFile += '</html>';

      if (defaults.outputMode === 'string')
        return docFile;

      if (defaults.outputMode === 'base64')
        return base64encode(docFile);

      saveToFile(docFile, defaults.fileName + '.' + MSDocExt, 'application/vnd.ms-' + MSDocType, '', 'base64', false);
    } else if (defaults.type === 'png') {
      html2canvas($(el)[0]).then(
        function (canvas) {

          const image = canvas.toDataURL();
          const byteString = atob(image.substring(22)); // remove data stuff
          const buffer = new ArrayBuffer(byteString.length);
          const intArray = new Uint8Array(buffer);

          for (let i = 0; i < byteString.length; i++)
            intArray[i] = byteString.charCodeAt(i);

          if (defaults.outputMode === 'string')
            return byteString;

          if (defaults.outputMode === 'base64')
            return base64encode(image);

          if (defaults.outputMode === 'window') {
            window.open(image);
            return;
          }

          saveToFile(buffer, defaults.fileName + '.png', 'image/png', '', '', false);
        });

    } else if (defaults.type === 'pdf') {

      if (defaults.pdfmake.enabled === true) {
        // pdf output using pdfmake
        // https://github.com/bpampuch/pdfmake

        const docDefinition = {
          content: []
        };

        $.extend(true, docDefinition, defaults.pdfmake.docDefinition);

        ranges = [];

        $(el).filter(function () {
          return isVisible($(this));
        }).each(function () {
          const $table = $(this);

          const widths = [];
          const body = [];
          rowIndex = 0;

          /**
           * @return {number}
           */
          const CollectPdfmakeData = function ($rows, colselector, length) {
            let rLength = 0;

            $($rows).each(function () {
              const r = [];

              ForEachVisibleCell(this, colselector, rowIndex, length,
                  function (cell, row, col) {
                    let cellContent;

                    if (typeof cell !== 'undefined' && cell !== null) {
                      const colspan = getColspan(cell);
                      const rowspan = getRowspan(cell);

                      cellContent = {text: parseString(cell, row, col) || ' '};

                      if (colspan > 1 || rowspan > 1) {
                        cellContent['colSpan'] = colspan || 1;
                        cellContent['rowSpan'] = rowspan || 1;
                      }
                    } else
                      cellContent = {text: ' '};

                    if (colselector.indexOf('th') >= 0)
                      cellContent['style'] = 'header';

                    r.push(cellContent);
                  });

              if (r.length)
                body.push(r);

              if (rLength < r.length)
                rLength = r.length;

              rowIndex++;
            });

            return rLength;
          };

          $head_rows = collectHeadRows($table);

          let colcount = CollectPdfmakeData($head_rows, 'th,td', $head_rows.length);

          for (let i = widths.length; i < colcount; i++)
            widths.push('*');

          // Data
          $rows = collectRows($table);

          colcount = CollectPdfmakeData($rows, 'td', $head_rows.length + $rows.length);

          for (let i = widths.length; i < colcount; i++)
            widths.push('*');

          docDefinition.content.push({ table: {
                                          headerRows: $head_rows.length ? $head_rows.length : null,
                                          widths: widths,
                                          body: body
                                        },
                                        layout: {
                                          layout: 'noBorders',
                                          hLineStyle: function (i, node) { return 0; },
                                          vLineWidth: function (i, node) { return 0; },
                                          hLineColor: function (i, node) { return i < node.table.headerRows ?
                                                        defaults.pdfmake.docDefinition.styles.header.background :
                                                        defaults.pdfmake.docDefinition.styles.alternateRow.fillColor; },
                                          vLineColor: function (i, node) { return i < node.table.headerRows ?
                                                        defaults.pdfmake.docDefinition.styles.header.background :
                                                        defaults.pdfmake.docDefinition.styles.alternateRow.fillColor; },
                                          fillColor: function (rowIndex, node, columnIndex) { return (rowIndex % 2 === 0) ?
                                                        defaults.pdfmake.docDefinition.styles.alternateRow.fillColor :
                                                        null; }
                                        },
                                        pageBreak: docDefinition.content.length ? "before" : undefined
                                     });
        }); // ...for each table

        if (typeof pdfMake !== 'undefined' && typeof pdfMake.createPdf !== 'undefined') {

          pdfMake.fonts = {
            Roboto: {
              normal: 'Roboto-Regular.ttf',
              bold: 'Roboto-Medium.ttf',
              italics: 'Roboto-Italic.ttf',
              bolditalics: 'Roboto-MediumItalic.ttf'
            }
          };

          // pdfmake >= 0.2.0 - replace pdfMake.vfs with pdfMake.virtualfs

          if (pdfMake.vfs.hasOwnProperty ('Mirza-Regular.ttf')) {
            docDefinition.defaultStyle.font = 'Mirza';
            $.extend(true, pdfMake.fonts, {Mirza: {normal:      'Mirza-Regular.ttf',
                                                   bold:        'Mirza-Bold.ttf',
                                                   italics:     'Mirza-Medium.ttf',
                                                   bolditalics: 'Mirza-SemiBold.ttf'
                                                   }});
          }
          else if (pdfMake.vfs.hasOwnProperty ('gbsn00lp.ttf')) {
            docDefinition.defaultStyle.font = 'gbsn00lp';
            $.extend(true, pdfMake.fonts, {gbsn00lp: {normal:      'gbsn00lp.ttf',
                                                      bold:        'gbsn00lp.ttf',
                                                      italics:     'gbsn00lp.ttf',
                                                      bolditalics: 'gbsn00lp.ttf'
                                                      }});
          }
          else if (pdfMake.vfs.hasOwnProperty ('ZCOOLXiaoWei-Regular.ttf')) {
            docDefinition.defaultStyle.font = 'ZCOOLXiaoWei';
            $.extend(true, pdfMake.fonts, {ZCOOLXiaoWei: {normal:      'ZCOOLXiaoWei-Regular.ttf',
                                                          bold:        'ZCOOLXiaoWei-Regular.ttf',
                                                          italics:     'ZCOOLXiaoWei-Regular.ttf',
                                                          bolditalics: 'ZCOOLXiaoWei-Regular.ttf'
                                                          }});
          }

          $.extend(true, pdfMake.fonts, defaults.pdfmake.fonts);

          // pdfmake <= 0.1.71
          pdfMake.createPdf(docDefinition).getBuffer(function (buffer) {
            saveToFile(buffer, defaults.fileName + '.pdf', 'application/pdf', '', '', false);
          });

          // pdfmake >= 0.2.0 - replace above code with:
          //pdfMake.createPdf(docDefinition).download(defaults.fileName);
        }
      } else if (defaults.jspdf.autotable === false) {
        // pdf output using jsPDF's core html support

        let doc = new jspdf.jsPDF({orientation: defaults.jspdf.orientation,
                                   unit: defaults.jspdf.unit,
                                   format: defaults.jspdf.format});
        doc.html(el[0], {
          callback: function () {
            jsPdfOutput(doc, false);
          },
          html2canvas: {scale: ((doc.internal.pageSize.width - defaults.jspdf.margins.left * 2) / el[0].scrollWidth)},
          x: defaults.jspdf.margins.left,
          y: defaults.jspdf.margins.top
          /*
          margin: [
            defaults.jspdf.margins.left,
            defaults.jspdf.margins.top,
            getPropertyUnitValue($(el).first().get(0), 'width', 'mm'),
            getPropertyUnitValue($(el).first().get(0), 'height', 'mm')
          ]
          */
        });
      } else {
        // pdf output using jsPDF AutoTable plugin
        // https://github.com/simonbengtsson/jsPDF-AutoTable

        const teOptions = defaults.jspdf.autotable.tableExport;

        // When setting jspdf.format to 'bestfit' tableExport tries to choose
        // the minimum required paper format and orientation in which the table
        // (or tables in multitable mode) completely fits without column adjustment
        if (typeof defaults.jspdf.format === 'string' && defaults.jspdf.format.toLowerCase() === 'bestfit') {
          let rk = '', ro = '';
          let mw = 0;

          $(el).each(function () {
            if (isVisible($(this))) {
              const w = getPropertyUnitValue($(this).get(0), 'width', 'pt');

              if (w > mw) {
                if (w > pageFormats.a0[0]) {
                  rk = 'a0';
                  ro = 'l';
                }
                for (let key in pageFormats) {
                  if (pageFormats.hasOwnProperty(key)) {
                    if (pageFormats[key][1] > w) {
                      rk = key;
                      ro = 'l';
                      if (pageFormats[key][0] > w)
                        ro = 'p';
                    }
                  }
                }
                mw = w;
              }
            }
          });
          defaults.jspdf.format = (rk === '' ? 'a4' : rk);
          defaults.jspdf.orientation = (ro === '' ? 'w' : ro);
        }

        // The jsPDF doc object is stored in defaults.jspdf.autotable.tableExport,
        // thus it can be accessed from any callback function
        if (teOptions.doc == null) {
          teOptions.doc = new jspdf.jsPDF(defaults.jspdf.orientation,
            defaults.jspdf.unit,
            defaults.jspdf.format);
          teOptions.wScaleFactor = 1;
          teOptions.hScaleFactor = 1;

          if (typeof defaults.jspdf.onDocCreated === 'function')
            defaults.jspdf.onDocCreated(teOptions.doc);
        }

        if (teOptions.outputImages === true)
          teOptions.images = {};

        if (typeof teOptions.images !== 'undefined') {
          $(el).filter(function () {
            return isVisible($(this));
          }).each(function () {
            let rowCount = 0;
            ranges = [];

            if (defaults.exportHiddenCells === false) {
              $hiddenTableElements = $(this).find('tr, th, td').filter(':hidden');
              checkCellVisibility = $hiddenTableElements.length > 0;
            }

            $head_rows = collectHeadRows($(this));
            $rows = collectRows($(this));

            $($rows).each(function () {
              ForEachVisibleCell(this, 'td,th', $head_rows.length + rowCount, $head_rows.length + $rows.length,
                function (cell) {
                  collectImages(cell, $(cell).children(), teOptions);
                });
              rowCount++;
            });
          });

          $head_rows = [];
          $rows = [];
        }

        loadImages(teOptions, function () {
          $(el).filter(function () {
            return isVisible($(this));
          }).each(function () {
            let colKey;
            rowIndex = 0;
            ranges = [];

            if (defaults.exportHiddenCells === false) {
              $hiddenTableElements = $(this).find('tr, th, td').filter(':hidden');
              checkCellVisibility = $hiddenTableElements.length > 0;
            }

            colNames = GetColumnNames(this);

            teOptions.columns = [];
            teOptions.rows = [];
            teOptions.teCells = {};

            // onTable: optional callback function for every matching table that can be used
            // to modify the tableExport options or to skip the output of a particular table
            // if the table selector targets multiple tables
            if (typeof teOptions.onTable === 'function')
              if (teOptions.onTable($(this), defaults) === false)
                return true; // continue to next iteration step (table)

            // each table works with an own copy of AutoTable options
            defaults.jspdf.autotable.tableExport = null;  // avoid deep recursion error
            const atOptions = $.extend(true, {}, defaults.jspdf.autotable);
            defaults.jspdf.autotable.tableExport = teOptions;

            atOptions.margin = {};
            $.extend(true, atOptions.margin, defaults.jspdf.margins);
            atOptions.tableExport = teOptions;

            if (typeof atOptions.createdHeaderCell !== 'function') {
              // apply some original css styles to pdf header cells
              atOptions.createdHeaderCell = function (cell, data) {

                if (typeof teOptions.columns [data.column.dataKey] !== 'undefined') {
                  const col = teOptions.columns [data.column.dataKey];

                  if (typeof col.rect !== 'undefined') {
                    let rh;

                    cell.contentWidth = col.rect.width;

                    if (typeof teOptions.heightRatio === 'undefined' || teOptions.heightRatio === 0) {
                      if (data.row.raw [data.column.dataKey].rowspan)
                        rh = data.row.raw [data.column.dataKey].rect.height / data.row.raw [data.column.dataKey].rowspan;
                      else
                        rh = data.row.raw [data.column.dataKey].rect.height;

                      teOptions.heightRatio = cell.styles.rowHeight / rh;
                    }

                    rh = data.row.raw [data.column.dataKey].rect.height * teOptions.heightRatio;
                    if (rh > cell.styles.rowHeight)
                      cell.styles.rowHeight = rh;
                  }

                  cell.styles.halign = (atOptions.headerStyles.halign === 'inherit') ? 'center' : atOptions.headerStyles.halign;
                  cell.styles.valign = atOptions.headerStyles.valign;

                  if (typeof col.style !== 'undefined' && col.style.hidden !== true) {
                    if (atOptions.headerStyles.halign === 'inherit')
                      cell.styles.halign = col.style.align;
                    if (atOptions.styles.fillColor === 'inherit')
                      cell.styles.fillColor = col.style.bcolor;
                    if (atOptions.styles.textColor === 'inherit')
                      cell.styles.textColor = col.style.color;
                    if (atOptions.styles.fontStyle === 'inherit')
                      cell.styles.fontStyle = col.style.fstyle;
                  }
                }
              };
            }

            if (typeof atOptions.createdCell !== 'function') {
              // apply some original css styles to pdf table cells
              atOptions.createdCell = function (cell, data) {
                const tecell = teOptions.teCells [data.row.index + ':' + data.column.dataKey];

                cell.styles.halign = (atOptions.styles.halign === 'inherit') ? 'center' : atOptions.styles.halign;
                cell.styles.valign = atOptions.styles.valign;

                if (typeof tecell !== 'undefined' && typeof tecell.style !== 'undefined' && tecell.style.hidden !== true) {
                  if (atOptions.styles.halign === 'inherit')
                    cell.styles.halign = tecell.style.align;
                  if (atOptions.styles.fillColor === 'inherit')
                    cell.styles.fillColor = tecell.style.bcolor;
                  if (atOptions.styles.textColor === 'inherit')
                    cell.styles.textColor = tecell.style.color;
                  if (atOptions.styles.fontStyle === 'inherit')
                    cell.styles.fontStyle = tecell.style.fstyle;
                }
              };
            }

            if (typeof atOptions.drawHeaderCell !== 'function') {
              atOptions.drawHeaderCell = function (cell, data) {
                const colopt = teOptions.columns [data.column.dataKey];

                if ((colopt.style.hasOwnProperty('hidden') !== true || colopt.style.hidden !== true) &&
                  colopt.rowIndex >= 0)
                  return prepareAutoTableText(cell, data, colopt);
                else
                  return false; // cell is hidden
              };
            }

            if (typeof atOptions.drawCell !== 'function') {
              atOptions.drawCell = function (cell, data) {
                const teCell = teOptions.teCells [data.row.index + ':' + data.column.dataKey];
                const draw2canvas = (typeof teCell !== 'undefined' && teCell.isCanvas);

                if (draw2canvas !== true) {
                  if (prepareAutoTableText(cell, data, teCell)) {

                    teOptions.doc.rect(cell.x, cell.y, cell.width, cell.height, cell.styles.fillStyle);

                    if (typeof teCell !== 'undefined' &&
                        (typeof teCell.hasUserDefText === 'undefined' || teCell.hasUserDefText !== true) &&
                        typeof teCell.elements !== 'undefined' && teCell.elements.length) {

                      const hScale = cell.height / teCell.rect.height;
                      if (hScale > teOptions.hScaleFactor)
                        teOptions.hScaleFactor = hScale;
                      teOptions.wScaleFactor = cell.width / teCell.rect.width;

                      const ySave = cell.textPos.y;
                      drawAutotableElements(cell, teCell.elements, teOptions);
                      cell.textPos.y = ySave;

                      drawAutotableText(cell, teCell.elements, teOptions);
                    } else
                      drawAutotableText(cell, {}, teOptions);
                  }
                } else {
                  const container = teCell.elements[0];
                  const imgId = $(container).attr('data-tableexport-canvas');
                  const r = container.getBoundingClientRect();

                  cell.width = r.width * teOptions.wScaleFactor;
                  cell.height = r.height * teOptions.hScaleFactor;
                  data.row.height = cell.height;

                  jsPdfDrawImage(cell, container, imgId, teOptions);
                }
                return false;
              };
            }

            // collect header and data rows
            teOptions.headerrows = [];
            $head_rows = collectHeadRows($(this));
            $($head_rows).each(function () {
              colKey = 0;
              teOptions.headerrows[rowIndex] = [];

              ForEachVisibleCell(this, 'th,td', rowIndex, $head_rows.length,
                function (cell, row, col) {
                  const obj = getCellStyles(cell);
                  obj.title = parseString(cell, row, col);
                  obj.key = colKey++;
                  obj.rowIndex = rowIndex;
                  teOptions.headerrows[rowIndex].push(obj);
                });
              rowIndex++;
            });

            if (rowIndex > 0) {
              // iterate through last row
              let lastrow = rowIndex - 1;
              while (lastrow >= 0) {
                $.each(teOptions.headerrows[lastrow], function () {
                  let obj = this;

                  if (lastrow > 0 && this.rect === null)
                    obj = teOptions.headerrows[lastrow - 1][this.key];

                  if (obj !== null && obj.rowIndex >= 0 &&
                    (obj.style.hasOwnProperty('hidden') !== true || obj.style.hidden !== true))
                    teOptions.columns.push(obj);
                });

                lastrow = (teOptions.columns.length > 0) ? -1 : lastrow - 1;
              }
            }

            let rowCount = 0;
            $rows = [];
            $rows = collectRows($(this));
            $($rows).each(function () {
              const rowData = [];
              colKey = 0;

              ForEachVisibleCell(this, 'td,th', rowIndex, $head_rows.length + $rows.length,
                function (cell, row, col) {
                  let obj;

                  if (typeof teOptions.columns[colKey] === 'undefined') {
                    // jsPDF-Autotable needs columns. Thus define hidden ones for tables without thead
                    obj = {
                      title: '',
                      key: colKey,
                      style: {
                        hidden: true
                      }
                    };
                    teOptions.columns.push(obj);
                  }

                  rowData.push(parseString(cell, row, col));

                  if (typeof cell !== 'undefined' && cell !== null) {
                    obj = getCellStyles(cell);
                    obj.isCanvas = cell.hasAttribute('data-tableexport-canvas');
                    obj.elements = obj.isCanvas ? $(cell) : $(cell).children();

                    if(typeof $(cell).data('teUserDefText') !== 'undefined')
                      obj.hasUserDefText = true;

                    teOptions.teCells [rowCount + ':' + colKey++] = obj;
                  } else {
                    obj = $.extend(true, {}, teOptions.teCells [rowCount + ':' + (colKey - 1)]);
                    obj.colspan = -1;
                    teOptions.teCells [rowCount + ':' + colKey++] = obj;
                  }
                });
              if (rowData.length) {
                teOptions.rows.push(rowData);
                rowCount++;
              }
              rowIndex++;
            });

            // onBeforeAutotable: optional callback function before calling
            // jsPDF AutoTable that can be used to modify the AutoTable options
            if (typeof teOptions.onBeforeAutotable === 'function')
              teOptions.onBeforeAutotable($(this), teOptions.columns, teOptions.rows, atOptions);

            jsPdfAutoTable(teOptions.doc, teOptions.columns, teOptions.rows, atOptions);

            // onAfterAutotable: optional callback function after returning
            // from jsPDF AutoTable that can be used to modify the AutoTable options
            if (typeof teOptions.onAfterAutotable === 'function')
              teOptions.onAfterAutotable($(this), atOptions);

            // set the start position for the next table (in case there is one)
            defaults.jspdf.autotable.startY = jsPdfAutoTableEndPosY() + atOptions.margin.top;

          });

          jsPdfOutput(teOptions.doc, (typeof teOptions.images !== 'undefined' && jQuery.isEmptyObject(teOptions.images) === false));

          if (typeof teOptions.headerrows !== 'undefined')
            teOptions.headerrows.length = 0;
          if (typeof teOptions.columns !== 'undefined')
            teOptions.columns.length = 0;
          if (typeof teOptions.rows !== 'undefined')
            teOptions.rows.length = 0;
          delete teOptions.doc;
          teOptions.doc = null;
        });
      }
    }

    function collectHeadRows ($table) {
      const result = [];
      findTableElements($table, 'thead').each(function () {
        result.push.apply(result, findTableElements($(this), defaults.theadSelector).toArray());
      });
      return result;
    }

    function collectRows ($table) {
      const result = [];
      findTableElements($table, 'tbody').each(function () {
        result.push.apply(result, findTableElements($(this), defaults.tbodySelector).toArray());
      });
      if (defaults.tfootSelector.length) {
        findTableElements($table, 'tfoot').each(function () {
          result.push.apply(result, findTableElements($(this), defaults.tfootSelector).toArray());
        });
      }
      return result;
    }

    function findTableElements ($parent, selector) {
      const parentSelector = $parent[0].tagName;
      const parentLevel = $parent.parents(parentSelector).length;
      return $parent.find(selector).filter(function () {
        return parentLevel === $(this).closest(parentSelector).parents(parentSelector).length;
      });
    }

    function GetColumnNames (table) {
      const result = [];
      let maxCols = 0;
      let row = 0;
      let col = 0;
      $(table).find('thead').first().find('th').each(function (index, el) {
        const hasDataField = $(el).attr('data-field') !== undefined;
        if (typeof el.parentNode.rowIndex !== 'undefined' && row !== el.parentNode.rowIndex) {
          row = el.parentNode.rowIndex;
          col = 0;
          maxCols = 0;
        }
        const colSpan = getColspan(el);
        maxCols += (colSpan ? colSpan : 1);
        while (col < maxCols) {
          result[col] = (hasDataField ? $(el).attr('data-field') : col.toString());
          col++;
        }
      });
      return result;
    }

    function isVisible ($element) {
      let isRow = typeof $element[0].rowIndex !== 'undefined';
      const isCell = isRow === false && typeof $element[0].cellIndex !== 'undefined';
      const isElementVisible = (isCell || isRow) ? isTableElementVisible($element) : $element.is(':visible');
      let tableexportDisplay = $element.attr('data-tableexport-display');

      if (isCell && tableexportDisplay !== 'none' && tableexportDisplay !== 'always') {
        $element = $($element[0].parentNode);
        isRow = typeof $element[0].rowIndex !== 'undefined';
        tableexportDisplay = $element.attr('data-tableexport-display');
      }
      if (isRow && tableexportDisplay !== 'none' && tableexportDisplay !== 'always') {
        tableexportDisplay = $element.closest('table').attr('data-tableexport-display');
      }

      return tableexportDisplay !== 'none' && (isElementVisible === true || tableexportDisplay === 'always');
    }

    function isTableElementVisible ($element) {
      let hiddenEls = [];

      if (checkCellVisibility) {
        hiddenEls = $hiddenTableElements.filter(function () {
          let found = false;

          if (this.nodeType === $element[0].nodeType) {
            if (typeof this.rowIndex !== 'undefined' && this.rowIndex === $element[0].rowIndex)
              found = true;
            else if (typeof this.cellIndex !== 'undefined' && this.cellIndex === $element[0].cellIndex &&
              typeof this.parentNode.rowIndex !== 'undefined' &&
              typeof $element[0].parentNode.rowIndex !== 'undefined' &&
              this.parentNode.rowIndex === $element[0].parentNode.rowIndex)
              found = true;
          }
          return found;
        });
      }
      return (checkCellVisibility === false || hiddenEls.length === 0);
    }

    function isColumnIgnored ($cell, rowLength, colIndex) {
      let result = false;

      if (isVisible($cell)) {
        if (defaults.ignoreColumn.length > 0) {
          if ($.inArray(colIndex, defaults.ignoreColumn) !== -1 ||
            $.inArray(colIndex - rowLength, defaults.ignoreColumn) !== -1 ||
            (colNames.length > colIndex && typeof colNames[colIndex] !== 'undefined' &&
              $.inArray(colNames[colIndex], defaults.ignoreColumn) !== -1))
            result = true;
        }
      } else
        result = true;

      return result;
    }

    function ForEachVisibleCell (tableRow, selector, rowIndex, rowCount, cellcallback) {
      if (typeof (cellcallback) === 'function') {
        let ignoreRow = false;

        if (typeof defaults.onIgnoreRow === 'function')
          ignoreRow = defaults.onIgnoreRow($(tableRow), rowIndex);

        if (ignoreRow === false &&
          (defaults.ignoreRow.length === 0 ||
            ($.inArray(rowIndex, defaults.ignoreRow) === -1 &&
              $.inArray(rowIndex - rowCount, defaults.ignoreRow) === -1)) &&
          isVisible($(tableRow))) {

          const $cells = findTableElements($(tableRow), selector);
          let cellsCount = $cells.length;
          let colCount = 0;
          let colIndex = 0;

          $cells.each(function () {
            const $cell = $(this);
            let colspan = getColspan(this);
            let rowspan = getRowspan(this);
            let c;

            // Skip ranges
            $.each(ranges, function () {
              const range = this;
              if (rowIndex > range.s.r && rowIndex <= range.e.r && colCount >= range.s.c && colCount <= range.e.c) {
                for (c = 0; c <= range.e.c - range.s.c; ++c) {
                  cellsCount++;
                  colIndex++;
                  cellcallback(null, rowIndex, colCount++);
                }
              }
            });

            // Handle span's
            if (rowspan || colspan) {
              rowspan = rowspan || 1;
              colspan = colspan || 1;
              ranges.push({
                s: {r: rowIndex, c: colCount},
                e: {r: rowIndex + rowspan - 1, c: colCount + colspan - 1}
              });
            }

            if (isColumnIgnored($cell, cellsCount, colIndex++) === false) {
              // Handle value
              cellcallback(this, rowIndex, colCount++);
            }

            // Handle colspan
            if (colspan > 1) {
              for (c = 0; c < colspan - 1; ++c) {
                colIndex++;
                cellcallback(null, rowIndex, colCount++);
              }
            }
          });

          // Skip ranges
          $.each(ranges, function () {
            const range = this;
            if (rowIndex >= range.s.r && rowIndex <= range.e.r && colCount >= range.s.c && colCount <= range.e.c) {
              for (let c = 0; c <= range.e.c - range.s.c; ++c) {
                cellcallback(null, rowIndex, colCount++);
              }
            }
          });
        }
      }
    }

    function jsPdfDrawImage (cell, container, imgId, teOptions) {
      if (typeof teOptions.images !== 'undefined') {
        const image = teOptions.images[imgId];

        if (typeof image !== 'undefined') {
          const r = container.getBoundingClientRect();
          const arCell = cell.width / cell.height;
          const arImg = r.width / r.height;
          let imgWidth = cell.width;
          let imgHeight = cell.height;
          const px2pt = 0.264583 * 72 / 25.4;
          let uy = 0;

          if (arImg <= arCell) {
            imgHeight = Math.min(cell.height, r.height);
            imgWidth = r.width * imgHeight / r.height;
          } else if (arImg > arCell) {
            imgWidth = Math.min(cell.width, r.width);
            imgHeight = r.height * imgWidth / r.width;
          }

          imgWidth *= px2pt;
          imgHeight *= px2pt;

          if (imgHeight < cell.height)
            uy = (cell.height - imgHeight) / 2;

          try {
            teOptions.doc.addImage(image.src, cell.textPos.x, cell.y + uy, imgWidth, imgHeight);
          } catch (e) {
            // TODO: IE -> convert png to jpeg
          }
          cell.textPos.x += imgWidth;
        }
      }
    }

    function jsPdfOutput (doc, hasimages) {
      if (defaults.outputMode === 'string')
        return doc.output();

      if (defaults.outputMode === 'base64')
        return base64encode(doc.output());

      if (defaults.outputMode === 'window') {
        window.URL = window.URL || window.webkitURL;
        window.open(window.URL.createObjectURL(doc.output('blob')));
        return;
      }

      try {
        const blob = doc.output('blob')
        saveAs(blob, defaults.fileName + '.pdf');
      } catch (e) {
        downloadFile(defaults.fileName + '.pdf',
          'data:application/pdf' + (hasimages ? '' : ';base64') + ',',
          hasimages ? doc.output('blob') : doc.output());
      }
    }

    function prepareAutoTableText (cell, data, cellopt) {
      let cs = 0
      if (typeof cellopt !== 'undefined')
        cs = cellopt.colspan;

      if (cs >= 0) {
        // colspan handling
        let cellWidth = cell.width
        let textPosX = cell.textPos.x
        const i = data.table.columns.indexOf(data.column)

        for (let c = 1; c < cs; c++) {
          const column = data.table.columns[i + c]
          cellWidth += column.width;
        }

        if (cs > 1) {
          if (cell.styles.halign === 'right')
            textPosX = cell.textPos.x + cellWidth - cell.width;
          else if (cell.styles.halign === 'center')
            textPosX = cell.textPos.x + (cellWidth - cell.width) / 2;
        }

        cell.width = cellWidth;
        cell.textPos.x = textPosX;

        if (typeof cellopt !== 'undefined' && cellopt.rowspan > 1)
          cell.height = cell.height * cellopt.rowspan;

        // fix jsPDF's calculation of text position
        if (cell.styles.valign === 'middle' || cell.styles.valign === 'bottom') {
          const splittedText = typeof cell.text === 'string' ? cell.text.split(/\r\n|\r|\n/g) : cell.text;
          const lineCount = splittedText.length || 1;
          if (lineCount > 2)
            cell.textPos.y -= ((2 - FONT_ROW_RATIO) / 2 * data.row.styles.fontSize) * (lineCount - 2) / 3;
        }
        return true;
      } else
        return false; // cell is hidden (colspan = -1), don't draw it
    }

    function collectImages (cell, elements, teOptions) {
      if (typeof cell !== 'undefined' && cell !== null) {

        if (cell.hasAttribute('data-tableexport-canvas')) {
          const imgId = new Date().getTime();
          $(cell).attr('data-tableexport-canvas', imgId);

          teOptions.images[imgId] = {
            url: '[data-tableexport-canvas="' + imgId + '"]',
            src: null
          };
        } else if (elements !== 'undefined' && elements != null) {
          elements.each(function () {
            if ($(this).is('img')) {
              const imgId = strHashCode(this.src);
              teOptions.images[imgId] = {
                url: this.src,
                src: this.src
              };
            }
            collectImages(cell, $(this).children(), teOptions);
          });
        }
      }
    }

    function loadImages (teOptions, callback) {
      let imageCount = 0;
      let pendingCount = 0;

      function done () {
        callback(imageCount);
      }

      function loadImage (image) {
        if (image.url) {
          if (!image.src) {
            const $imgContainer = $(image.url);
            if ($imgContainer.length) {
              imageCount = ++pendingCount;

              html2canvas($imgContainer[0]).then(function (canvas) {
                image.src = canvas.toDataURL('image/png');
                if (!--pendingCount)
                  done();
              });
            }
          } else {
            const img = new Image();
            imageCount = ++pendingCount;
            img.crossOrigin = 'Anonymous';
            img.onerror = img.onload = function () {
              if (img.complete) {

                if (img.src.indexOf('data:image/') === 0) {
                  img.width = image.width || img.width || 0;
                  img.height = image.height || img.height || 0;
                }

                if (img.width + img.height) {
                  const canvas = document.createElement('canvas');
                  const ctx = canvas.getContext('2d');

                  canvas.width = img.width;
                  canvas.height = img.height;
                  ctx.drawImage(img, 0, 0);

                  image.src = canvas.toDataURL('image/png');
                }
              }
              if (!--pendingCount)
                done();
            };
            img.src = image.url;
          }
        }
      }

      if (typeof teOptions.images !== 'undefined') {
        for (let i in teOptions.images)
          if (teOptions.images.hasOwnProperty(i))
            loadImage(teOptions.images[i]);
      }

      return pendingCount || done();
    }

    function drawAutotableElements (cell, elements, teOptions) {
      elements.each(function () {
        if ($(this).is('div')) {
          const bColor = rgb2array(getStyle(this, 'background-color'), [255, 255, 255]);
          const lColor = rgb2array(getStyle(this, 'border-top-color'), [0, 0, 0]);
          const lWidth = getPropertyUnitValue(this, 'border-top-width', defaults.jspdf.unit);

          const r = this.getBoundingClientRect();
          const ux = this.offsetLeft * teOptions.wScaleFactor;
          const uy = this.offsetTop * teOptions.hScaleFactor;
          const uw = r.width * teOptions.wScaleFactor;
          const uh = r.height * teOptions.hScaleFactor;

          teOptions.doc.setDrawColor.apply(undefined, lColor);
          teOptions.doc.setFillColor.apply(undefined, bColor);
          teOptions.doc.setLineWidth(lWidth);
          teOptions.doc.rect(cell.x + ux, cell.y + uy, uw, uh, lWidth ? 'FD' : 'F');
        } else if ($(this).is('img')) {
          const imgId = strHashCode(this.src);
          jsPdfDrawImage(cell, this, imgId, teOptions);
        }

        drawAutotableElements(cell, $(this).children(), teOptions);
      });
    }

    function drawAutotableText (cell, texttags, teOptions) {
      if (typeof teOptions.onAutotableText === 'function') {
        teOptions.onAutotableText(teOptions.doc, cell, texttags);
      } else {
        let x = cell.textPos.x;
        let y = cell.textPos.y;
        const style = {halign: cell.styles.halign, valign: cell.styles.valign};

        if (texttags.length) {
          let tag = texttags[0];
          while (tag.previousSibling)
            tag = tag.previousSibling;

          let b = false, i = false;

          while (tag) {
            let txt = tag.innerText || tag.textContent || '';
            const leadingSpace = (txt.length && txt[0] === ' ') ? ' ' : '';
            const trailingSpace = (txt.length > 1 && txt[txt.length - 1] === ' ') ? ' ' : '';

            if (defaults.preserve.leadingWS !== true)
              txt = leadingSpace + trimLeft(txt);
            if (defaults.preserve.trailingWS !== true)
              txt = trimRight(txt) + trailingSpace;

            if ($(tag).is('br')) {
              x = cell.textPos.x;
              y += teOptions.doc.internal.getFontSize();
            }

            if ($(tag).is('b'))
              b = true;
            else if ($(tag).is('i'))
              i = true;

            if (b || i)
              teOptions.doc.setFont('undefined ', (b && i) ? 'bolditalic' : b ? 'bold' : 'italic');

            let w = teOptions.doc.getStringUnitWidth(txt) * teOptions.doc.internal.getFontSize();

            if (w) {
              if (cell.styles.overflow === 'linebreak' &&
                x > cell.textPos.x && (x + w) > (cell.textPos.x + cell.width)) {
                const chars = '.,!%*;:=-';
                if (chars.indexOf(txt.charAt(0)) >= 0) {
                  const s = txt.charAt(0);
                  w = teOptions.doc.getStringUnitWidth(s) * teOptions.doc.internal.getFontSize();
                  if ((x + w) <= (cell.textPos.x + cell.width)) {
                    jsPdfAutoTableText(s, x, y, style);
                    txt = txt.substring(1, txt.length);
                  }
                  w = teOptions.doc.getStringUnitWidth(txt) * teOptions.doc.internal.getFontSize();
                }
                x = cell.textPos.x;
                y += teOptions.doc.internal.getFontSize();
              }

              if (cell.styles.overflow !== 'visible') {
                while (txt.length && (x + w) > (cell.textPos.x + cell.width)) {
                  txt = txt.substring(0, txt.length - 1);
                  w = teOptions.doc.getStringUnitWidth(txt) * teOptions.doc.internal.getFontSize();
                }
              }

              jsPdfAutoTableText(txt, x, y, style);
              x += w;
            }

            if (b || i) {
              if ($(tag).is('b'))
                b = false;
              else if ($(tag).is('i'))
                i = false;

              teOptions.doc.setFont('undefined ', (!b && !i) ? 'normal' : b ? 'bold' : 'italic');
            }

            tag = tag.nextSibling;
          }
          cell.textPos.x = x;
          cell.textPos.y = y;
        } else {
          jsPdfAutoTableText(cell.text, cell.textPos.x, cell.textPos.y, style);
        }
      }
    }

    function escapeRegExp (string) {
      return string == null ? '' : string.toString().replace(/([.*+?^=!:${}()|\[\]\/\\])/g, '\\$1');
    }

    function replaceAll (string, find, replace) {
      return string == null ? '' : string.toString().replace(new RegExp(escapeRegExp(find), 'g'), replace);
    }

    function trimLeft (string) {
      return string == null ? '' : string.toString().replace(/^\s+/, '');
    }

    function trimRight (string) {
      return string == null ? '' : string.toString().replace(/\s+$/, '');
    }

    function parseDateUTC (s) {
      if (defaults.date.html.length === 0)
        return false;

      defaults.date.pattern.lastIndex = 0;

      const match = defaults.date.pattern.exec(s);
      if (match == null)
        return false;

      const y = +match[defaults.date.match_y];
      if (y < 0 || y > 8099) return false;
      const m = match[defaults.date.match_m] * 1;
      const d = match[defaults.date.match_d] * 1;
      if (!isFinite(d)) return false;

      const o = new Date(y, m - 1, d, 0, 0, 0);
      if (o.getFullYear() === y && o.getMonth() === (m - 1) && o.getDate() === d)
        return new Date(Date.UTC(y, m - 1, d, 0, 0, 0));
      else
        return false;
    }

    function parseNumber (value) {
      value = value || '0';
      if ('' !== defaults.numbers.html.thousandsSeparator)
        value = replaceAll(value, defaults.numbers.html.thousandsSeparator, '');
      if ('.' !== defaults.numbers.html.decimalMark)
        value = replaceAll(value, defaults.numbers.html.decimalMark, '.');

      return typeof value === 'number' || jQuery.isNumeric(value) !== false ? value : false;
    }

    function parsePercent (value) {
      if (value.indexOf('%') > -1) {
        value = parseNumber(value.replace(/%/g, ''));
        if (value !== false)
          value = value / 100;
      } else
        value = false;
      return value;
    }

    function parseString (cell, rowIndex, colIndex, cellInfo) {
      let result = '';
      let cellType = 'text';

      if (cell !== null) {
        const $cell = $(cell);
        let htmlData;

        $cell.removeData('teUserDefText');

        if ($cell[0].hasAttribute('data-tableexport-canvas')) {
          htmlData = '';
        } else if ($cell[0].hasAttribute('data-tableexport-value')) {
          htmlData = $cell.attr('data-tableexport-value');
          htmlData = htmlData ? htmlData + '' : '';
          $cell.data('teUserDefText', 1);
        } else {
          htmlData = $cell.html();

          if (typeof defaults.onCellHtmlData === 'function') {
            htmlData = defaults.onCellHtmlData($cell, rowIndex, colIndex, htmlData);
            $cell.data('teUserDefText', 1);
          }
          else if (htmlData !== '') {
            const html = $.parseHTML(htmlData);
            let inputIndex = 0;
            let selectIndex = 0;

            htmlData = '';
            $.each(html, function () {
              if ($(this).is('input')) {
                htmlData += $cell.find('input').eq(inputIndex++).val();
              }
              else if ($(this).is('select')) {
                htmlData += $cell.find('select option:selected').eq(selectIndex++).text();
              }
              else if ($(this).is('br')) {
                htmlData += '<br>';
              }
              else {
                if (typeof $(this).html() === 'undefined')
                  htmlData += $(this).text();
                else if (jQuery().bootstrapTable === undefined ||
                  ($(this).hasClass('fht-cell') === false &&  // BT 4
                    $(this).hasClass('filterControl') === false &&
                    $cell.parents('.detail-view').length === 0))
                  htmlData += $(this).html();

                if ($(this).is('a')) {
                  const href = $cell.find('a').attr('href') || '';
                  if (typeof defaults.onCellHtmlHyperlink === 'function') {
                    result += defaults.onCellHtmlHyperlink($cell, rowIndex, colIndex, href, htmlData);
                  }
                  else if (defaults.htmlHyperlink === 'href') {
                    result += href;
                  }
                  else { // 'content'
                    result += htmlData;
                  }
                  htmlData = '';
                }
              }
            });
          }
        }

        if (htmlData && htmlData !== '' && defaults.htmlContent === true) {
          result = $.trim(htmlData);
        } else if (htmlData && htmlData !== '') {
          const cellFormat = $cell.attr('data-tableexport-cellformat');

          if (cellFormat !== '') {
            let text = htmlData.replace(/\n/g, '\u2028').replace(/(<\s*br([^>]*)>)/gi, '\u2060');
            const obj = $('<div/>').html(text).contents();
            let number = false;
            text = '';

            $.each(obj.text().split('\u2028'), function (i, v) {
              if (i > 0)
                text += ' ';

              if (defaults.preserve.leadingWS !== true)
                v = trimLeft(v);
              text += (defaults.preserve.trailingWS !== true) ? trimRight(v) : v;
            });

            $.each(text.split('\u2060'), function (i, v) {
              if (i > 0)
                result += '\n';

              if (defaults.preserve.leadingWS !== true)
                v = trimLeft(v);
              if (defaults.preserve.trailingWS !== true)
                v = trimRight(v);
              result += v.replace(/\u00AD/g, ''); // remove soft hyphens
            });

            result = result.replace(/\u00A0/g, ' '); // replace nbsp's with spaces

            if (defaults.type === 'json' ||
              (defaults.type === 'excel' && defaults.mso.fileFormat === 'xmlss') ||
              defaults.numbers.output === false) {
              number = parseNumber(result);

              if (number !== false) {
                cellType = 'number';
                result = Number(number);
              }
            } else if (defaults.numbers.html.decimalMark !== defaults.numbers.output.decimalMark ||
              defaults.numbers.html.thousandsSeparator !== defaults.numbers.output.thousandsSeparator) {
              number = parseNumber(result);

              if (number !== false) {
                const frac = ('' + number.substr(number < 0 ? 1 : 0)).split('.');
                if (frac.length === 1)
                  frac[1] = '';
                const mod = frac[0].length > 3 ? frac[0].length % 3 : 0;

                cellType = 'number';
                result = (number < 0 ? '-' : '') +
                  (defaults.numbers.output.thousandsSeparator ? ((mod ? frac[0].substr(0, mod) + defaults.numbers.output.thousandsSeparator : '') + frac[0].substr(mod).replace(/(\d{3})(?=\d)/g, '$1' + defaults.numbers.output.thousandsSeparator)) : frac[0]) +
                  (frac[1].length ? defaults.numbers.output.decimalMark + frac[1] : '');
              }
            }
          }
          else
            result = htmlData;
        }

        if (defaults.escape === true) {
          //noinspection JSDeprecatedSymbols
          result = escape(result);
        }

        if (typeof defaults.onCellData === 'function') {
          result = defaults.onCellData($cell, rowIndex, colIndex, result, cellType);
          $cell.data('teUserDefText', 1);
        }
      }

      if (cellInfo !== undefined)
        cellInfo.type = cellType;

      return result;
    }

    function preventInjection (str) {
      if (str.length > 0 && defaults.preventInjection === true) {
        const chars = '=+-@';
        if (chars.indexOf(str.charAt(0)) >= 0)
          return ('\'' + str);
      }
      return str;
    }

    //noinspection JSUnusedLocalSymbols
    function hyphenate (a, b, c) {
      return b + '-' + c.toLowerCase();
    }

    function rgb2array (rgb_string, default_result) {
      const re = /^rgb\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3})\)$/;
      const bits = re.exec(rgb_string);
      let result = default_result;
      if (bits)
        result = [parseInt(bits[1]), parseInt(bits[2]), parseInt(bits[3])];
      return result;
    }

    function getCellStyles (cell) {
      let a = getStyle(cell, 'text-align');
      const fw = getStyle(cell, 'font-weight');
      const fs = getStyle(cell, 'font-style');
      let f = '';
      if (a === 'start')
        a = getStyle(cell, 'direction') === 'rtl' ? 'right' : 'left';
      if (fw >= 700)
        f = 'bold';
      if (fs === 'italic')
        f += fs;
      if (f === '')
        f = 'normal';

      const result = {
        style: {
          align: a,
          bcolor: rgb2array(getStyle(cell, 'background-color'), [255, 255, 255]),
          color: rgb2array(getStyle(cell, 'color'), [0, 0, 0]),
          fstyle: f
        },
        colspan: getColspan(cell),
        rowspan: getRowspan(cell)
      };

      if (cell !== null) {
        const r = cell.getBoundingClientRect();
        result.rect = {
          width: r.width,
          height: r.height
        };
      }

      return result;
    }

    function getColspan (cell) {
      let result = $(cell).attr('data-tableexport-colspan');
      if (typeof result === 'undefined' && $(cell).is('[colspan]'))
        result = $(cell).attr('colspan');

      return (parseInt(result) || 0);
    }

    function getRowspan (cell) {
      let result = $(cell).attr('data-tableexport-rowspan');
      if (typeof result === 'undefined' && $(cell).is('[rowspan]'))
        result = $(cell).attr('rowspan');

      return (parseInt(result) || 0);
    }

    // get computed style property
    function getStyle (target, prop) {
      try {
        if (window.getComputedStyle) { // gecko and webkit
          prop = prop.replace(/([a-z])([A-Z])/, hyphenate);  // requires hyphenated, not camel
          return window.getComputedStyle(target, null).getPropertyValue(prop);
        }
        if (target.currentStyle) { // ie
          return target.currentStyle[prop];
        }
        return target.style[prop];
      } catch (e) {
      }
      return '';
    }

    function getUnitValue (parent, value, unit) {
      const baseline = 100;  // any number serves

      const temp = document.createElement('div');  // create temporary element
      temp.style.overflow = 'hidden';  // in case baseline is set too low
      temp.style.visibility = 'hidden';  // no need to show it

      parent.appendChild(temp); // insert it into the parent for em, ex and %

      temp.style.width = baseline + unit;
      const factor = baseline / temp.offsetWidth;

      parent.removeChild(temp);  // clean up

      return (value * factor);
    }

    function getPropertyUnitValue (target, prop, unit) {
      const value = getStyle(target, prop);  // get the computed style value

      let numeric = value.match(/\d+/);  // get the numeric component
      if (numeric !== null) {
        numeric = numeric[0];  // get the string

        return getUnitValue(target.parentElement, numeric, unit);
      }
      return 0;
    }

    function xlsxWorkbookToArrayBuffer (s) {
      const buf = new ArrayBuffer(s.length);
      const view = new Uint8Array(buf);
      for (let i = 0; i !== s.length; ++i) view[i] = s.charCodeAt(i) & 0xFF;
      return buf;
    }

    function xlsxTableToSheet (table) {
      let ssfId;
      const ws = ({});
      const rows = table.getElementsByTagName('tr');
      const sheetRows = Math.min(10000000, rows.length);
      const range = {s: {r: 0, c: 0}, e: {r: 0, c: 0}};
      let merges = [], midx = 0;
      let _R = 0, R = 0, _C = 0, C = 0, RS = 0, CS = 0;
      let elt;
      const ssfTable = XLSX.SSF.get_table();

      for (; _R < rows.length && R < sheetRows; ++_R) {
        const row = rows[_R];

        let ignoreRow = false;
        if (typeof defaults.onIgnoreRow === 'function')
          ignoreRow = defaults.onIgnoreRow($(row), _R);

        if (ignoreRow === true ||
            (defaults.ignoreRow.length !== 0 &&
             ($.inArray(_R, defaults.ignoreRow) !== -1 ||
              $.inArray(_R - rows.length, defaults.ignoreRow) !== -1)) ||
            isVisible($(row)) === false) {
          continue;
        }

        const elts = (row.children);
        let _CLength = 0;
        for (_C = 0; _C < elts.length; ++_C) {
          elt = elts[_C];
          CS = +getColspan(elt) || 1;
          _CLength += CS;
        }

        let CSOffset = 0;
        for (_C = C = 0; _C < elts.length; ++_C) {
          elt = elts[_C];
          CS = +getColspan(elt) || 1;

          const col = _C + CSOffset;
          if (isColumnIgnored($(elt), _CLength, col + (col < C ? C - col : 0)))
            continue;
          CSOffset += CS - 1;

          for (midx = 0; midx < merges.length; ++midx) {
            const m = merges[midx];
            if (m.s.c == C && m.s.r <= R && R <= m.e.r) {
              C = m.e.c + 1;
              midx = -1;
            }
          }

          if ((RS = +getRowspan(elt)) > 0 || CS > 1)
            merges.push({s: {r: R, c: C}, e: {r: R + (RS || 1) - 1, c: C + CS - 1}});

          const cellInfo = {type: ''};
          let v = parseString(elt, _R, _C + CSOffset, cellInfo);
          let o = {t: 's', v: v};
          let _t = '';
          const cellFormat = $(elt).attr('data-tableexport-cellformat') || '';

          if (cellFormat !== '') {
            ssfId = parseInt($(elt).attr('data-tableexport-xlsxformatid') || 0);

            if (ssfId === 0 &&
              typeof defaults.mso.xslx.formatId.numbers === 'function')
              ssfId = defaults.mso.xslx.formatId.numbers($(elt), _R, _C + CSOffset);

            if (ssfId === 0 &&
              typeof defaults.mso.xslx.formatId.date === 'function')
              ssfId = defaults.mso.xslx.formatId.date($(elt), _R, _C + CSOffset);

            if (ssfId === 49 || ssfId === '@')
              _t = 's';
            else if (cellInfo.type === 'number' ||
              (ssfId > 0 && ssfId < 14) || (ssfId > 36 && ssfId < 41) || ssfId === 48)
              _t = 'n';
            else if (cellInfo.type === 'date' ||
              (ssfId > 13 && ssfId < 37) || (ssfId > 44 && ssfId < 48) || ssfId === 56)
              _t = 'd';
          } else
            _t = 's';

          if (v != null) {
            let vd;

            if (v.length === 0) {
              o.t = 'z';
            }
            else if (v.trim().length === 0) {
            }
            else if (_t === 's') {
            }
            else if (cellInfo.type === 'function') {
              o = {f: v};
            }
            else if (v === 'TRUE') {
              o = {t: 'b', v: true};
            }
            else if (v === 'FALSE') {
              o = {t: 'b', v: false};
            }
            else if (_t === 'n' || isFinite(xlsxToNumber(v, defaults.numbers.output))) { // yes, defaults.numbers.output is right
              const vn = xlsxToNumber(v, defaults.numbers.output);
              if (ssfId === 0 && typeof defaults.mso.xslx.formatId.numbers !== 'function') {
                ssfId = defaults.mso.xslx.formatId.numbers;
              }
              if (isFinite(vn) || isFinite(v))
                o = {
                  t: 'n',
                  v: (isFinite(vn) ? vn : v),
                  z: (typeof ssfId === 'string') ? ssfId : (ssfId in ssfTable ? ssfTable[ssfId] : '0.00')
                };
            }
            else if ((vd = parseDateUTC(v)) !== false || _t === 'd') {
              if (ssfId === 0 && typeof defaults.mso.xslx.formatId.date !== 'function') {
                ssfId = defaults.mso.xslx.formatId.date;
              }
              o = {
                t: 'd',
                v: (vd !== false ? vd : v),
                z: (typeof ssfId === 'string') ? ssfId : (ssfId in ssfTable ? ssfTable[ssfId] : 'm/d/yy')
              };
            }
            const $aTag = $(elt).find('a');
            if ($aTag && $aTag.length) {
              const href = $aTag[0].hasAttribute("href") ? $aTag.attr('href') : '';
              const content = (defaults.htmlHyperlink !== 'href' || href === '') ? v : '';
              const hyperlink = (href !== '') ? '=HYPERLINK("' + href + (content.length ? '","' + content : '') + '")' : '';

              if (hyperlink !== '') {
                if (typeof defaults.mso.xlsx.onHyperlink === 'function') {
                  v = defaults.mso.xlsx.onHyperlink($(elt), _R, _C, href, content, hyperlink);
                  if (v.indexOf('=HYPERLINK') !== 0) {
                    o = {t: 's', v: v};
                  } else {
                    o = {f: v};
                  }
                } else {
                  o = {f: hyperlink};
                }
              }
            }
          }
          ws[xlsxEncodeCell({c: C, r: R})] = o;
          if (range.e.c < C) {
            range.e.c = C;
          }
          C += CS;
        }
        ++R;
      }
      if (merges.length) {
        ws['!merges'] = (ws["!merges"] || []).concat(merges);
      }
      range.e.r = Math.max(range.e.r, R - 1);
      ws['!ref'] = xlsxEncodeRange(range);
      if (R >= sheetRows) {
        ws['!fullref'] = xlsxEncodeRange((range.e.r = rows.length - _R + R - 1, range));
      }
      return ws;
    }

    function xlsxEncodeRow (row) {
      return '' + (row + 1);
    }

    function xlsxEncodeCol (col) {
      let s = '';
      for (++col; col; col = Math.floor((col - 1) / 26)) {
        s = String.fromCharCode(((col - 1) % 26) + 65) + s;
      }
      return s;
    }

    function xlsxEncodeCell (cell) {
      return xlsxEncodeCol(cell.c) + xlsxEncodeRow(cell.r);
    }

    function xlsxEncodeRange (cs, ce) {
      if (typeof ce === 'undefined' || typeof ce === 'number') {
        return xlsxEncodeRange(cs.s, cs.e);
      }
      if (typeof cs !== 'string') {
        cs = xlsxEncodeCell((cs));
      }
      if (typeof ce !== 'string') {
        ce = xlsxEncodeCell((ce));
      }
      return cs === ce ? cs : cs + ':' + ce;
    }

    function xlsxToNumber (s, numbersFormat) {
      let v = Number(s);
      if (isFinite(v)) return v;
      let wt = 1;
      let ss = s;
      if ('' !== numbersFormat.thousandsSeparator)
        ss = ss.replace(new RegExp('([\\d])' + numbersFormat.thousandsSeparator + '([\\d])', 'g'), '$1$2');
      if ('.' !== numbersFormat.decimalMark)
        ss = ss.replace(new RegExp('([\\d])' + numbersFormat.decimalMark + '([\\d])', 'g'), '$1.$2');
      ss = ss.replace(/[$]/g, '').replace(/[%]/g, function () {
        wt *= 100;
        return '';
      });
      if (isFinite(v = Number(ss))) return v / wt;
      ss = ss.replace(/[(](.*)[)]/, function ($$, $1) {
        wt = -wt;
        return $1;
      });
      if (isFinite(v = Number(ss))) return v / wt;
      return v;
    }

    function strHashCode (str) {
      let hash = 0, i, chr, len;
      if (str.length === 0) return hash;
      for (i = 0, len = str.length; i < len; i++) {
        chr = str.charCodeAt(i);
        hash = ((hash << 5) - hash) + chr;
        hash |= 0; // Convert to 32bit integer
      }
      return hash;
    }

    function saveToFile (data, fileName, type, charset, encoding, bom) {
      let saveIt = true;
      if (typeof defaults.onBeforeSaveToFile === 'function') {
        saveIt = defaults.onBeforeSaveToFile(data, fileName, type, charset, encoding);
        if (typeof saveIt !== 'boolean')
          saveIt = true;
      }

      if (saveIt) {
        try {
          blob = new Blob([data], {type: type + ';charset=' + charset});
          saveAs(blob, fileName, bom === false);

          if (typeof defaults.onAfterSaveToFile === 'function')
            defaults.onAfterSaveToFile(data, fileName);
        } catch (e) {
          downloadFile(fileName,
            'data:' + type +
            (charset.length ? ';charset=' + charset : '') +
            (encoding.length ? ';' + encoding : '') + ',',
            (bom ? ('\ufeff' + data) : data));
        }
      }
    }

    function downloadFile (filename, header, data) {
      const ua = window.navigator.userAgent;
      if (filename !== false && window.navigator.msSaveOrOpenBlob) {
        //noinspection JSUnresolvedFunction
        window.navigator.msSaveOrOpenBlob(new Blob([data]), filename);
      } else if (filename !== false && (ua.indexOf('MSIE ') > 0 || !!ua.match(/Trident.*rv\:11\./))) {
        // Internet Explorer (<= 9) workaround by Darryl (https://github.com/dawiong/tableExport.jquery.plugin)
        // based on sampopes answer on http://stackoverflow.com/questions/22317951
        // ! Not working for json and pdf format !
        const frame = document.createElement('iframe');

        if (frame) {
          document.body.appendChild(frame);
          frame.setAttribute('style', 'display:none');
          frame.contentDocument.open('txt/plain', 'replace');
          frame.contentDocument.write(data);
          frame.contentDocument.close();
          frame.contentWindow.focus();

          const extension = filename.substr((filename.lastIndexOf('.') + 1));
          switch (extension) {
            case 'doc':
            case 'json':
            case 'png':
            case 'pdf':
            case 'xls':
            case 'xlsx':
              filename += '.txt';
              break;
          }
          frame.contentDocument.execCommand('SaveAs', true, filename);
          document.body.removeChild(frame);
        }
      } else {
        const DownloadLink = document.createElement('a');

        if (DownloadLink) {
          let blobUrl = null;

          DownloadLink.style.display = 'none';
          if (filename !== false)
            DownloadLink.download = filename;
          else
            DownloadLink.target = '_blank';

          if (typeof data === 'object') {
            window.URL = window.URL || window.webkitURL;
            const binaryData = [];
            binaryData.push(data);
            blobUrl = window.URL.createObjectURL(new Blob(binaryData, {type: header}));
            DownloadLink.href = blobUrl;
          }
          else if (header.toLowerCase().indexOf('base64,') >= 0) {
            DownloadLink.href = header + base64encode(data);
          }
          else {
            DownloadLink.href = header + encodeURIComponent(data);
          }

          document.body.appendChild(DownloadLink);

          if (document.createEvent) {
            if (DownloadEvt === null)
              DownloadEvt = document.createEvent('MouseEvents');

            DownloadEvt.initEvent('click', true, false);
            DownloadLink.dispatchEvent(DownloadEvt);
          }
          else if (document.createEventObject)
            DownloadLink.fireEvent('onclick');
          else if (typeof DownloadLink.onclick === 'function')
            DownloadLink.onclick();

          setTimeout(function () {
            if (blobUrl)
              window.URL.revokeObjectURL(blobUrl);
            document.body.removeChild(DownloadLink);

            if (typeof defaults.onAfterSaveToFile === 'function')
              defaults.onAfterSaveToFile(data, filename);
          }, 100);
        }
      }
    }

    function utf8Encode (text) {
      if (typeof text === 'string') {
        text = text.replace(/\x0d\x0a/g, '\x0a');
        let utfText = '';
        for (let n = 0; n < text.length; n++) {
          const c = text.charCodeAt(n);
          if (c < 128) {
            utfText += String.fromCharCode(c);
          } else if ((c > 127) && (c < 2048)) {
            utfText += String.fromCharCode((c >> 6) | 192);
            utfText += String.fromCharCode((c & 63) | 128);
          } else {
            utfText += String.fromCharCode((c >> 12) | 224);
            utfText += String.fromCharCode(((c >> 6) & 63) | 128);
            utfText += String.fromCharCode((c & 63) | 128);
          }
        }
        return utfText;
      }
      return text;
    }

    function base64encode (input) {
      let chr1, chr2, chr3, enc1, enc2, enc3, enc4;
      const keyStr = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
      let output = '';
      let i = 0;
      input = utf8Encode(input);
      while (i < input.length) {
        chr1 = input.charCodeAt(i++);
        chr2 = input.charCodeAt(i++);
        chr3 = input.charCodeAt(i++);
        enc1 = chr1 >> 2;
        enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
        enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
        enc4 = chr3 & 63;
        if (isNaN(chr2)) {
          enc3 = enc4 = 64;
        } else if (isNaN(chr3)) {
          enc4 = 64;
        }
        output = output +
          keyStr.charAt(enc1) + keyStr.charAt(enc2) +
          keyStr.charAt(enc3) + keyStr.charAt(enc4);
      }
      return output;
    }

    // ----------------------------------------------------------------------------------------------------
    // jsPDF-AutoTable 2.0.17 - BEGIN
    // Adopted and adapted source code from https://github.com/simonbengtsson/jsPDF-AutoTable
    // ----------------------------------------------------------------------------------------------------

    var jsPdfDoc, // The current jspdf instance
        jsPdfCursor, // An object keeping track of the x and y position of the next table cell to draw
        jsPdfSettings, // Default options merged with user options
        jsPdfPageCount, // The  page count the current table spans
        jsPdfTable; // The current Table instance

    function jsPdfAutoTable (doc, headers, data, options) {
      jsPdfValidateInput(headers, data, options);
      jsPdfDoc = doc;
      jsPdfSettings = jsPdfInitOptions(options || {});
      jsPdfPageCount = 1;

      // Need a cursor y as it needs to be reset after each page (row.y can't do that)
      jsPdfCursor = { y: jsPdfSettings.startY === false ? jsPdfSettings.margin.top : jsPdfSettings.startY };

      const userStyles = {
        textColor: 30, // Setting text color to dark gray as it can't be obtained from jsPDF
        fontSize: jsPdfDoc.internal.getFontSize(),
        fontStyle: jsPdfDoc.internal.getFont().fontStyle
      };

      // Create the table model with its columns, rows and cells
      jsPdfCreateModels(headers, data);
      jsPdfCalculateWidths();

      // Page break if there is room for only the first data row
      const firstRowHeight = jsPdfTable.rows[0] && jsPdfSettings.pageBreak === 'auto' ? jsPdfTable.rows[0].height : 0;
      let minTableBottomPos = jsPdfSettings.startY + jsPdfSettings.margin.bottom + jsPdfTable.headerRow.height + firstRowHeight;
      if (jsPdfSettings.pageBreak === 'avoid') {
        minTableBottomPos += jsPdfTable.height;
      }
      if ((jsPdfSettings.pageBreak === 'always' && jsPdfSettings.startY !== false) ||
        (jsPdfSettings.startY !== false && minTableBottomPos > jsPdfDoc.internal.pageSize.height)) {
        jsPdfDoc.addPage();
        jsPdfCursor.y = jsPdfSettings.margin.top;
      }

      jsPdfApplyStyles(userStyles);
      jsPdfSettings.beforePageContent(jsPdfHooksData());
      if (jsPdfSettings.drawHeaderRow(jsPdfTable.headerRow, jsPdfHooksData({row: jsPdfTable.headerRow})) !== false) {
        jsPdfPrintRow(jsPdfTable.headerRow, jsPdfSettings.drawHeaderCell);
      }
      jsPdfApplyStyles(userStyles);
      jsPdfPrintRows();
      jsPdfSettings.afterPageContent(jsPdfHooksData());

      jsPdfApplyStyles(userStyles);

      return jsPdfDoc;
    }

    /**
     * Returns the Y position of the last drawn cell
     * @returns int
     */
    function jsPdfAutoTableEndPosY () {
      if (typeof jsPdfCursor === 'undefined' || typeof jsPdfCursor.y === 'undefined') {
        return 0;
      }
      return jsPdfCursor.y;
    }

    /**
     * Improved text function with halign and valign support
     * Inspiration from:
     * http://stackoverflow.com/questions/28327510/align-text-right-using-jspdf/28433113#28433113
     */
    function jsPdfAutoTableText (text, x, y, styles) {
      if (typeof x !== 'number' || typeof y !== 'number') {
        console.error('The x and y parameters are required. Missing for the text: ', text);
      }
      const fontSize = jsPdfDoc.internal.getFontSize() / jsPdfDoc.internal.scaleFactor;

      // As defined in jsPDF source code
      const lineHeightProportion = FONT_ROW_RATIO;

      const splitRegex = /\r\n|\r|\n/g;
      let splittedText = null;
      let lineCount = 1;
      if (styles.valign === 'middle' || styles.valign === 'bottom' || styles.halign === 'center' || styles.halign === 'right') {
        splittedText = typeof text === 'string' ? text.split(splitRegex) : text;

        lineCount = splittedText.length || 1;
      }

      // Align the top
      y += fontSize * (2 - lineHeightProportion);

      if (styles.valign === 'middle')
        y -= (lineCount / 2) * fontSize;
      else if (styles.valign === 'bottom')
        y -= lineCount * fontSize;

      if (styles.halign === 'center' || styles.halign === 'right') {
        let alignSize = fontSize;
        if (styles.halign === 'center')
          alignSize *= 0.5;

        if (splittedText && lineCount >= 1) {
          for (let iLine = 0; iLine < splittedText.length; iLine++) {
            jsPdfDoc.text(splittedText[iLine], x - jsPdfDoc.getStringUnitWidth(splittedText[iLine]) * alignSize, y);
            y += fontSize;
          }
          return jsPdfDoc;
        }
        x -= jsPdfDoc.getStringUnitWidth(text) * alignSize;
      }

      jsPdfDoc.text(text, x, y);
      return jsPdfDoc;
    }

    function jsPdfValidateInput(headers, data, options) {
      if (!headers || typeof headers !== 'object') {
        console.error("The headers should be an object or array, is: " + typeof headers);
      }

      if (!data || typeof data !== 'object') {
        console.error("The data should be an object or array, is: " + typeof data);
      }

      if (!!options && typeof options !== 'object') {
        console.error("The data should be an object or array, is: " + typeof data);
      }

      if (!Array.prototype.forEach) {
        console.error("The current browser does not support Array.prototype.forEach which is required for jsPDF-AutoTable");
      }
    }

    function jsPdfInitOptions(userOptions) {
      const settings = jsPdfExtend(jsPdfDefaultOptions(), userOptions);

      // Options
      if (typeof settings.extendWidth !== 'undefined') {
        settings.tableWidth = settings.extendWidth ? 'auto' : 'wrap';
        console.error("Use of deprecated option: extendWidth, use tableWidth instead.");
      }
      if (typeof settings.margins !== 'undefined') {
        if (typeof settings.margin === 'undefined') settings.margin = settings.margins;
        console.error("Use of deprecated option: margins, use margin instead.");
      }

      [['padding', 'cellPadding'], ['lineHeight', 'rowHeight'], 'fontSize', 'overflow'].forEach(function (o) {
        const deprecatedOption = typeof o === 'string' ? o : o[0];
        const style = typeof o === 'string' ? o : o[1];
        if (typeof settings[deprecatedOption] !== 'undefined') {
          if (typeof settings.styles[style] === 'undefined') {
            settings.styles[style] = settings[deprecatedOption];
          }
          console.error("Use of deprecated option: " + deprecatedOption + ", use the style " + style + " instead.");
        }
      });

      // Unifying
      const marginSetting = settings.margin;
      settings.margin = {};
      if (typeof marginSetting.horizontal === 'number') {
        marginSetting.right = marginSetting.horizontal;
        marginSetting.left = marginSetting.horizontal;
      }
      if (typeof marginSetting.vertical === 'number') {
        marginSetting.top = marginSetting.vertical;
        marginSetting.bottom = marginSetting.vertical;
      }
      ['top', 'right', 'bottom', 'left'].forEach(function (side, i) {
        if (typeof marginSetting === 'number') {
          settings.margin[side] = marginSetting;
        } else {
          const key = Array.isArray(marginSetting) ? i : side;
          settings.margin[side] = typeof marginSetting[key] === 'number' ? marginSetting[key] : 40;
        }
      });

      return settings;
    }

    /**
     * Create models from the user input
     *
     * @param inputHeaders
     * @param inputData
     */
    function jsPdfCreateModels(inputHeaders, inputData) {
      jsPdfTable = new jsPdfTableClass();
      jsPdfTable.x = jsPdfSettings.margin.left;

      const splitRegex = /\r\n|\r|\n/g;

      // Header row and columns
      const headerRow = new jsPdfRowClass(inputHeaders);
      headerRow.index = -1;

      const themeStyles = jsPdfExtend(jsPdfDefaultStyles, jsPdfThemes[jsPdfSettings.theme].table, jsPdfThemes[jsPdfSettings.theme].header);
      headerRow.styles = jsPdfExtend(themeStyles, jsPdfSettings.styles, jsPdfSettings.headerStyles);

      // Columns and header row
      inputHeaders.forEach(function (rawColumn, dataKey) {
        if (typeof rawColumn === 'object') {
          dataKey = typeof rawColumn.dataKey !== 'undefined' ? rawColumn.dataKey : rawColumn.key;
        }

        if (typeof rawColumn.width !== 'undefined') {
          console.error("Use of deprecated option: column.width, use column.styles.columnWidth instead.");
        }

        const col = new jsPdfColumnClass(dataKey);
        col.styles = jsPdfSettings.columnStyles[col.dataKey] || {};
        jsPdfTable.columns.push(col);

        const cell = new jsPdfCellClass();
        cell.raw = typeof rawColumn === 'object' ? rawColumn.title : rawColumn;

        // jsPDF AutoTable plugin v2.0.14 fix: each cell needs its own styles object
        //cell.styles = jsPdfExtend(headerRow.styles);
        cell.styles = $.extend({}, headerRow.styles);

        cell.text = '' + cell.raw;
        cell.contentWidth = cell.styles.cellPadding * 2 + jsPdfGetStringWidth(cell.text, cell.styles);
        cell.text = cell.text.split(splitRegex);

        headerRow.cells[dataKey] = cell;
        jsPdfSettings.createdHeaderCell(cell, {column: col, row: headerRow, settings: jsPdfSettings});
      });
      jsPdfTable.headerRow = headerRow;

      // Rows och cells
      inputData.forEach(function (rawRow, i) {
        const row = new jsPdfRowClass(rawRow);
        const isAlternate = i % 2 === 0;
        const themeStyles = jsPdfExtend(jsPdfDefaultStyles, jsPdfThemes[jsPdfSettings.theme].table, isAlternate ? jsPdfThemes[jsPdfSettings.theme].alternateRow : {});
        const userStyles = jsPdfExtend(jsPdfSettings.styles, jsPdfSettings.bodyStyles, isAlternate ? jsPdfSettings.alternateRowStyles : {});
        row.styles = jsPdfExtend(themeStyles, userStyles);
        row.index = i;
        jsPdfTable.columns.forEach(function (column) {
          const cell = new jsPdfCellClass();
          cell.raw = rawRow[column.dataKey];
          cell.styles = jsPdfExtend(row.styles, column.styles);
          cell.text = typeof cell.raw !== 'undefined' ? '' + cell.raw : ''; // Stringify 0 and false, but not undefined
          row.cells[column.dataKey] = cell;
          jsPdfSettings.createdCell(cell, jsPdfHooksData({column: column, row: row}));
          cell.contentWidth = cell.styles.cellPadding * 2 + jsPdfGetStringWidth(cell.text, cell.styles);
          cell.text = cell.text.split(splitRegex);
        });
        jsPdfTable.rows.push(row);
      });
    }

    /**
     * Calculate the column widths
     */
    function jsPdfCalculateWidths() {
      // Column and table content width
      let tableContentWidth = 0;
      jsPdfTable.columns.forEach(function (column) {
        column.contentWidth = jsPdfTable.headerRow.cells[column.dataKey].contentWidth;
        jsPdfTable.rows.forEach(function (row) {
          const cellWidth = row.cells[column.dataKey].contentWidth;
          if (cellWidth > column.contentWidth) {
            column.contentWidth = cellWidth;
          }
        });
        column.width = column.contentWidth;
        tableContentWidth += column.contentWidth;
      });
      jsPdfTable.contentWidth = tableContentWidth;

      const maxTableWidth = jsPdfDoc.internal.pageSize.width - jsPdfSettings.margin.left - jsPdfSettings.margin.right;
      let preferredTableWidth = maxTableWidth; // settings.tableWidth === 'auto'
      if (typeof jsPdfSettings.tableWidth === 'number') {
        preferredTableWidth = jsPdfSettings.tableWidth;
      } else if (jsPdfSettings.tableWidth === 'wrap') {
        preferredTableWidth = jsPdfTable.contentWidth;
      }
      jsPdfTable.width = preferredTableWidth < maxTableWidth ? preferredTableWidth : maxTableWidth;

      // To avoid subjecting columns with little content with the chosen overflow method,
      // never shrink a column more than the table divided by column count (its "fair part")
      const dynamicColumns = [];
      let dynamicColumnsContentWidth = 0;
      const fairWidth = jsPdfTable.width / jsPdfTable.columns.length;
      let staticWidth = 0;
      jsPdfTable.columns.forEach(function (column) {
        const colStyles = jsPdfExtend(jsPdfDefaultStyles, jsPdfThemes[jsPdfSettings.theme].table, jsPdfSettings.styles, column.styles);
        if (colStyles.columnWidth === 'wrap') {
          column.width = column.contentWidth;
        } else if (typeof colStyles.columnWidth === 'number') {
          column.width = colStyles.columnWidth;
        } else if (colStyles.columnWidth === 'auto' || true) {
          if (column.contentWidth <= fairWidth && jsPdfTable.contentWidth > jsPdfTable.width) {
            column.width = column.contentWidth;
          } else {
            dynamicColumns.push(column);
            dynamicColumnsContentWidth += column.contentWidth;
            column.width = 0;
          }
        }
        staticWidth += column.width;
      });

      // Distributes extra width or trims columns down to fit
      jsPdfDistributeWidth(dynamicColumns, staticWidth, dynamicColumnsContentWidth, fairWidth);

      // Row height, table height and text overflow
      jsPdfTable.height = 0;
      const all = jsPdfTable.rows.concat(jsPdfTable.headerRow);
      all.forEach(function (row, i) {
        let lineBreakCount = 0;
        let cursorX = jsPdfTable.x;
        jsPdfTable.columns.forEach(function (col) {
          const cell = row.cells[col.dataKey];
          col.x = cursorX;
          jsPdfApplyStyles(cell.styles);
          const textSpace = col.width - cell.styles.cellPadding * 2;
          if (cell.styles.overflow === 'linebreak') {
            // Add one pt to textSpace to fix rounding error
            cell.text = jsPdfDoc.splitTextToSize(cell.text, textSpace + 1, {fontSize: cell.styles.fontSize});
          } else if (cell.styles.overflow === 'ellipsize') {
            cell.text = jsPdfEllipsize(cell.text, textSpace, cell.styles);
          } else if (cell.styles.overflow === 'visible') {
            // Do nothing
          } else if (cell.styles.overflow === 'hidden') {
            cell.text = jsPdfEllipsize(cell.text, textSpace, cell.styles, '');
          } else if (typeof cell.styles.overflow === 'function') {
            cell.text = cell.styles.overflow(cell.text, textSpace);
          } else {
            console.error("Unrecognized overflow type: " + cell.styles.overflow);
          }
          const count = Array.isArray(cell.text) ? cell.text.length - 1 : 0;
          if (count > lineBreakCount) {
            lineBreakCount = count;
          }
          cursorX += col.width;
        });

        row.heightStyle = row.styles.rowHeight;
        // TODO Pick the highest row based on font size as well
        row.height = (row.heightStyle + lineBreakCount * row.styles.fontSize * FONT_ROW_RATIO) +
                     ((2 - FONT_ROW_RATIO) / 2 * row.styles.fontSize); // Fix jsPDF Autotable's row height calculation

        jsPdfTable.height += row.height;
      });
    }

    function jsPdfDistributeWidth(dynamicColumns, staticWidth, dynamicColumnsContentWidth, fairWidth) {
      const extraWidth = jsPdfTable.width - staticWidth - dynamicColumnsContentWidth;
      for (let i = 0; i < dynamicColumns.length; i++) {
        const col = dynamicColumns[i];
        const ratio = col.contentWidth / dynamicColumnsContentWidth;
        // A column turned out to be none dynamic, start over recursively
        const isNoneDynamic = col.contentWidth + extraWidth * ratio < fairWidth;
        if (extraWidth < 0 && isNoneDynamic) {
          dynamicColumns.splice(i, 1);
          dynamicColumnsContentWidth -= col.contentWidth;
          col.width = fairWidth;
          staticWidth += col.width;
          jsPdfDistributeWidth(dynamicColumns, staticWidth, dynamicColumnsContentWidth, fairWidth);
          break;
        } else {
          col.width = col.contentWidth + extraWidth * ratio;
        }
      }
    }

    function jsPdfPrintRows() {
      jsPdfTable.rows.forEach(function (row, i) {
        if (jsPdfIsNewPage(row.height)) {
          jsPdfAddPage();
        }
        row.y = jsPdfCursor.y;
        if (jsPdfSettings.drawRow(row, jsPdfHooksData({row: row})) !== false) {
          jsPdfPrintRow(row, jsPdfSettings.drawCell);
        }
      });
    }

    function jsPdfAddPage() {
      jsPdfSettings.afterPageContent(jsPdfHooksData());
      jsPdfDoc.addPage();
      jsPdfPageCount++;
      jsPdfCursor = {x: jsPdfSettings.margin.left, y: jsPdfSettings.margin.top};
      jsPdfSettings.beforePageContent(jsPdfHooksData());
      if (jsPdfSettings.drawHeaderRow(jsPdfTable.headerRow, jsPdfHooksData({row: jsPdfTable.headerRow})) !== false) {
        jsPdfPrintRow(jsPdfTable.headerRow, jsPdfSettings.drawHeaderCell);
      }
    }

    /**
     * Add a new page if cursor is at the end of page
     * @param rowHeight
     * @returns {boolean}
     */
    function jsPdfIsNewPage(rowHeight) {
      const afterRowPos = jsPdfCursor.y + rowHeight + jsPdfSettings.margin.bottom;
      return afterRowPos >= jsPdfDoc.internal.pageSize.height;
    }

    function jsPdfPrintRow(row, hookHandler) {
      for (let i = 0; i < jsPdfTable.columns.length; i++) {
        const column = jsPdfTable.columns[i];
        const cell = row.cells[column.dataKey];
        if(!cell) {
          continue;
        }
        jsPdfApplyStyles(cell.styles);

        cell.x = column.x;
        cell.y = jsPdfCursor.y;
        cell.height = row.height;
        cell.width = column.width;

        if (cell.styles.valign === 'top') {
          cell.textPos.y = jsPdfCursor.y + cell.styles.cellPadding;
        } else if (cell.styles.valign === 'bottom') {
          cell.textPos.y = jsPdfCursor.y + row.height - cell.styles.cellPadding;
        } else {
          cell.textPos.y = jsPdfCursor.y + row.height / 2;
        }

        if (cell.styles.halign === 'right') {
          cell.textPos.x = cell.x + cell.width - cell.styles.cellPadding;
        } else if (cell.styles.halign === 'center') {
          cell.textPos.x = cell.x + cell.width / 2;
        } else {
          cell.textPos.x = cell.x + cell.styles.cellPadding;
        }

        const data = jsPdfHooksData({column: column, row: row});
        if (hookHandler(cell, data) !== false) {
          jsPdfDoc.rect(cell.x, cell.y, cell.width, cell.height, cell.styles.fillStyle);
          jsPdfAutoTableText(cell.text, cell.textPos.x, cell.textPos.y, {
            halign: cell.styles.halign,
            valign: cell.styles.valign
          });
        }
      }

      jsPdfCursor.y += row.height;
    }

    function jsPdfApplyStyles(styles) {
      const arr = [
        {func: jsPdfDoc.setFillColor, value: styles.fillColor},
        {func: jsPdfDoc.setTextColor, value: styles.textColor},
        {func: jsPdfDoc.setFont, value: styles.font, style: styles.fontStyle},
        {func: jsPdfDoc.setDrawColor, value: styles.lineColor},
        {func: jsPdfDoc.setLineWidth, value: styles.lineWidth},
        {func: jsPdfDoc.setFont, value: styles.font},
        {func: jsPdfDoc.setFontSize, value: styles.fontSize}
      ];
      arr.forEach(function (obj) {
        if (typeof obj.value !== 'undefined') {
          if (obj.value.constructor === Array) {
            obj.func.apply(jsPdfDoc, obj.value);
          } else if (typeof obj.style !== 'undefined') {
            obj.func(obj.value, obj.style);
          } else {
            obj.func(obj.value);
          }
        }
      });
    }

    function jsPdfHooksData(additionalData) {
      additionalData = additionalData || {};
      const data = {
        pageCount: jsPdfPageCount,
        settings: jsPdfSettings,
        table: jsPdfTable,
        cursor: jsPdfCursor
      };
      for (let prop in additionalData) {
        if (additionalData.hasOwnProperty(prop)) {
          data[prop] = additionalData[prop];
        }
      }
      return data;
    }

    /**
     * Ellipsize the text to fit in the width
     */
    function jsPdfEllipsize(text, width, styles, ellipsizeStr) {
      ellipsizeStr = typeof  ellipsizeStr !== 'undefined' ? ellipsizeStr : '...';

      if (Array.isArray(text)) {
        text.forEach(function (str, i) {
          text[i] = jsPdfEllipsize(str, width, styles, ellipsizeStr);
        });
        return text;
      }

      if (width >= jsPdfGetStringWidth(text, styles)) {
        return text;
      }
      while (width < jsPdfGetStringWidth(text + ellipsizeStr, styles)) {
        if (text.length < 2) {
          break;
        }
        text = text.substring(0, text.length - 1);
      }
      return text.trim() + ellipsizeStr;
    }

    function jsPdfGetStringWidth(text, styles) {
      jsPdfApplyStyles(styles);
      const w = jsPdfDoc.getStringUnitWidth(text);
      return w * styles.fontSize;
    }

    function jsPdfExtend(defaults) {
      const extended = {};
      let prop;
      for (prop in defaults) {
        if (defaults.hasOwnProperty(prop)) {
          extended[prop] = defaults[prop];
        }
      }
      for (let i = 1; i < arguments.length; i++) {
        const options = arguments[i]
        for (prop in options) {
          if (options.hasOwnProperty(prop)) {
            extended[prop] = options[prop];
          }
        }
      }
      return extended;
    }

    // ----------------------------------------------------------------------------------------------------
    // jsPDF-AutoTable 2.0.17 - END
    // ----------------------------------------------------------------------------------------------------

    if (typeof defaults.onTableExportEnd === 'function')
      defaults.onTableExportEnd();

    return this;
  };

  // See README.md for documentation of the options
  // See examples.js for usage examples
  function jsPdfDefaultOptions () {
    return {
      // Styling
      theme: 'striped', // 'striped', 'grid' or 'plain'
      styles: {},
      headerStyles: {},
      bodyStyles: {},
      alternateRowStyles: {},
      columnStyles: {},

      // Properties
      startY: false, // false indicates the margin.top value
      margin: 40,
      pageBreak: 'auto', // 'auto', 'avoid', 'always'
      tableWidth: 'auto', // number, 'auto', 'wrap'

      // Hooks
      createdHeaderCell: function (cell, data) {},
      createdCell: function (cell, data) {},
      drawHeaderRow: function (row, data) {},
      drawRow: function (row, data) {},
      drawHeaderCell: function (cell, data) {},
      drawCell: function (cell, data) {},
      beforePageContent: function (data) {},
      afterPageContent: function (data) {}
    }
  }

  var jsPdfTableClass = /** class */ (function () {
    function jsPdfTableClass() { /** constructor */
      this.height = 0;
      this.width = 0;
      this.x = 0;
      this.y = 0;
      this.contentWidth = 0;
      this.rows = [];
      this.columns = [];
      this.headerRow = null;
      this.settings = {};
    }
    return jsPdfTableClass;
  }());

  var jsPdfRowClass = /** class */ (function () {
    function jsPdfRowClass(raw) { /** constructor */
      this.raw = raw || {};
      this.index = 0;
      this.styles = {};
      this.cells = {};
      this.height = 0;
      this.y = 0;
    }
    return jsPdfRowClass;
  }());

  var jsPdfCellClass = /** class */ (function () {
    function jsPdfCellClass(raw) { /** constructor */
      this.raw = raw;
      this.styles = {};
      this.text = '';
      this.contentWidth = 0;
      this.textPos = {};
      this.height = 0;
      this.width = 0;
      this.x = 0;
      this.y = 0;
    }
    return jsPdfCellClass;
  }());

  var jsPdfColumnClass = /** class */ (function () {
    function jsPdfColumnClass(dataKey) { /** constructor */
      this.dataKey = dataKey;
      this.options = {};
      this.styles = {};
      this.contentWidth = 0;
      this.width = 0;
      this.x = 0;
    }
    return jsPdfColumnClass;
  }());

})(jQuery);




(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? factory(require('jquery')) :
  typeof define === 'function' && define.amd ? define(['jquery'], factory) :
  (global = typeof globalThis !== 'undefined' ? globalThis : global || self, factory(global.jQuery));
}(this, (function ($) { 'use strict';

  function _interopDefaultLegacy (e) { return e && typeof e === 'object' && 'default' in e ? e : { 'default': e }; }

  var $__default = /*#__PURE__*/_interopDefaultLegacy($);

  function _classCallCheck(instance, Constructor) {
    if (!(instance instanceof Constructor)) {
      throw new TypeError("Cannot call a class as a function");
    }
  }

  function _defineProperties(target, props) {
    for (var i = 0; i < props.length; i++) {
      var descriptor = props[i];
      descriptor.enumerable = descriptor.enumerable || false;
      descriptor.configurable = true;
      if ("value" in descriptor) descriptor.writable = true;
      Object.defineProperty(target, descriptor.key, descriptor);
    }
  }

  function _createClass(Constructor, protoProps, staticProps) {
    if (protoProps) _defineProperties(Constructor.prototype, protoProps);
    if (staticProps) _defineProperties(Constructor, staticProps);
    return Constructor;
  }

  function _defineProperty(obj, key, value) {
    if (key in obj) {
      Object.defineProperty(obj, key, {
        value: value,
        enumerable: true,
        configurable: true,
        writable: true
      });
    } else {
      obj[key] = value;
    }

    return obj;
  }

  function _inherits(subClass, superClass) {
    if (typeof superClass !== "function" && superClass !== null) {
      throw new TypeError("Super expression must either be null or a function");
    }

    subClass.prototype = Object.create(superClass && superClass.prototype, {
      constructor: {
        value: subClass,
        writable: true,
        configurable: true
      }
    });
    if (superClass) _setPrototypeOf(subClass, superClass);
  }

  function _getPrototypeOf(o) {
    _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) {
      return o.__proto__ || Object.getPrototypeOf(o);
    };
    return _getPrototypeOf(o);
  }

  function _setPrototypeOf(o, p) {
    _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) {
      o.__proto__ = p;
      return o;
    };

    return _setPrototypeOf(o, p);
  }

  function _isNativeReflectConstruct() {
    if (typeof Reflect === "undefined" || !Reflect.construct) return false;
    if (Reflect.construct.sham) return false;
    if (typeof Proxy === "function") return true;

    try {
      Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {}));
      return true;
    } catch (e) {
      return false;
    }
  }

  function _assertThisInitialized(self) {
    if (self === void 0) {
      throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
    }

    return self;
  }

  function _possibleConstructorReturn(self, call) {
    if (call && (typeof call === "object" || typeof call === "function")) {
      return call;
    }

    return _assertThisInitialized(self);
  }

  function _createSuper(Derived) {
    var hasNativeReflectConstruct = _isNativeReflectConstruct();

    return function _createSuperInternal() {
      var Super = _getPrototypeOf(Derived),
          result;

      if (hasNativeReflectConstruct) {
        var NewTarget = _getPrototypeOf(this).constructor;

        result = Reflect.construct(Super, arguments, NewTarget);
      } else {
        result = Super.apply(this, arguments);
      }

      return _possibleConstructorReturn(this, result);
    };
  }

  function _superPropBase(object, property) {
    while (!Object.prototype.hasOwnProperty.call(object, property)) {
      object = _getPrototypeOf(object);
      if (object === null) break;
    }

    return object;
  }

  function _get(target, property, receiver) {
    if (typeof Reflect !== "undefined" && Reflect.get) {
      _get = Reflect.get;
    } else {
      _get = function _get(target, property, receiver) {
        var base = _superPropBase(target, property);

        if (!base) return;
        var desc = Object.getOwnPropertyDescriptor(base, property);

        if (desc.get) {
          return desc.get.call(receiver);
        }

        return desc.value;
      };
    }

    return _get(target, property, receiver || target);
  }

  function _unsupportedIterableToArray(o, minLen) {
    if (!o) return;
    if (typeof o === "string") return _arrayLikeToArray(o, minLen);
    var n = Object.prototype.toString.call(o).slice(8, -1);
    if (n === "Object" && o.constructor) n = o.constructor.name;
    if (n === "Map" || n === "Set") return Array.from(o);
    if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen);
  }

  function _arrayLikeToArray(arr, len) {
    if (len == null || len > arr.length) len = arr.length;

    for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i];

    return arr2;
  }

  function _createForOfIteratorHelper(o, allowArrayLike) {
    var it;

    if (typeof Symbol === "undefined" || o[Symbol.iterator] == null) {
      if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") {
        if (it) o = it;
        var i = 0;

        var F = function () {};

        return {
          s: F,
          n: function () {
            if (i >= o.length) return {
              done: true
            };
            return {
              done: false,
              value: o[i++]
            };
          },
          e: function (e) {
            throw e;
          },
          f: F
        };
      }

      throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
    }

    var normalCompletion = true,
        didErr = false,
        err;
    return {
      s: function () {
        it = o[Symbol.iterator]();
      },
      n: function () {
        var step = it.next();
        normalCompletion = step.done;
        return step;
      },
      e: function (e) {
        didErr = true;
        err = e;
      },
      f: function () {
        try {
          if (!normalCompletion && it.return != null) it.return();
        } finally {
          if (didErr) throw err;
        }
      }
    };
  }

  var commonjsGlobal = typeof globalThis !== 'undefined' ? globalThis : typeof window !== 'undefined' ? window : typeof global !== 'undefined' ? global : typeof self !== 'undefined' ? self : {};

  function createCommonjsModule(fn, module) {
  	return module = { exports: {} }, fn(module, module.exports), module.exports;
  }

  var check = function (it) {
    return it && it.Math == Math && it;
  };

  // https://github.com/zloirock/core-js/issues/86#issuecomment-115759028
  var global_1 =
    /* global globalThis -- safe */
    check(typeof globalThis == 'object' && globalThis) ||
    check(typeof window == 'object' && window) ||
    check(typeof self == 'object' && self) ||
    check(typeof commonjsGlobal == 'object' && commonjsGlobal) ||
    // eslint-disable-next-line no-new-func -- fallback
    (function () { return this; })() || Function('return this')();

  var fails = function (exec) {
    try {
      return !!exec();
    } catch (error) {
      return true;
    }
  };

  // Detect IE8's incomplete defineProperty implementation
  var descriptors = !fails(function () {
    return Object.defineProperty({}, 1, { get: function () { return 7; } })[1] != 7;
  });

  var nativePropertyIsEnumerable = {}.propertyIsEnumerable;
  var getOwnPropertyDescriptor$1 = Object.getOwnPropertyDescriptor;

  // Nashorn ~ JDK8 bug
  var NASHORN_BUG = getOwnPropertyDescriptor$1 && !nativePropertyIsEnumerable.call({ 1: 2 }, 1);

  // `Object.prototype.propertyIsEnumerable` method implementation
  // https://tc39.es/ecma262/#sec-object.prototype.propertyisenumerable
  var f$4 = NASHORN_BUG ? function propertyIsEnumerable(V) {
    var descriptor = getOwnPropertyDescriptor$1(this, V);
    return !!descriptor && descriptor.enumerable;
  } : nativePropertyIsEnumerable;

  var objectPropertyIsEnumerable = {
  	f: f$4
  };

  var createPropertyDescriptor = function (bitmap, value) {
    return {
      enumerable: !(bitmap & 1),
      configurable: !(bitmap & 2),
      writable: !(bitmap & 4),
      value: value
    };
  };

  var toString = {}.toString;

  var classofRaw = function (it) {
    return toString.call(it).slice(8, -1);
  };

  var split = ''.split;

  // fallback for non-array-like ES3 and non-enumerable old V8 strings
  var indexedObject = fails(function () {
    // throws an error in rhino, see https://github.com/mozilla/rhino/issues/346
    // eslint-disable-next-line no-prototype-builtins -- safe
    return !Object('z').propertyIsEnumerable(0);
  }) ? function (it) {
    return classofRaw(it) == 'String' ? split.call(it, '') : Object(it);
  } : Object;

  // `RequireObjectCoercible` abstract operation
  // https://tc39.es/ecma262/#sec-requireobjectcoercible
  var requireObjectCoercible = function (it) {
    if (it == undefined) throw TypeError("Can't call method on " + it);
    return it;
  };

  // toObject with fallback for non-array-like ES3 strings



  var toIndexedObject = function (it) {
    return indexedObject(requireObjectCoercible(it));
  };

  var isObject = function (it) {
    return typeof it === 'object' ? it !== null : typeof it === 'function';
  };

  // `ToPrimitive` abstract operation
  // https://tc39.es/ecma262/#sec-toprimitive
  // instead of the ES6 spec version, we didn't implement @@toPrimitive case
  // and the second argument - flag - preferred type is a string
  var toPrimitive = function (input, PREFERRED_STRING) {
    if (!isObject(input)) return input;
    var fn, val;
    if (PREFERRED_STRING && typeof (fn = input.toString) == 'function' && !isObject(val = fn.call(input))) return val;
    if (typeof (fn = input.valueOf) == 'function' && !isObject(val = fn.call(input))) return val;
    if (!PREFERRED_STRING && typeof (fn = input.toString) == 'function' && !isObject(val = fn.call(input))) return val;
    throw TypeError("Can't convert object to primitive value");
  };

  var hasOwnProperty = {}.hasOwnProperty;

  var has$1 = function (it, key) {
    return hasOwnProperty.call(it, key);
  };

  var document$1 = global_1.document;
  // typeof document.createElement is 'object' in old IE
  var EXISTS = isObject(document$1) && isObject(document$1.createElement);

  var documentCreateElement = function (it) {
    return EXISTS ? document$1.createElement(it) : {};
  };

  // Thank's IE8 for his funny defineProperty
  var ie8DomDefine = !descriptors && !fails(function () {
    return Object.defineProperty(documentCreateElement('div'), 'a', {
      get: function () { return 7; }
    }).a != 7;
  });

  var nativeGetOwnPropertyDescriptor = Object.getOwnPropertyDescriptor;

  // `Object.getOwnPropertyDescriptor` method
  // https://tc39.es/ecma262/#sec-object.getownpropertydescriptor
  var f$3 = descriptors ? nativeGetOwnPropertyDescriptor : function getOwnPropertyDescriptor(O, P) {
    O = toIndexedObject(O);
    P = toPrimitive(P, true);
    if (ie8DomDefine) try {
      return nativeGetOwnPropertyDescriptor(O, P);
    } catch (error) { /* empty */ }
    if (has$1(O, P)) return createPropertyDescriptor(!objectPropertyIsEnumerable.f.call(O, P), O[P]);
  };

  var objectGetOwnPropertyDescriptor = {
  	f: f$3
  };

  var anObject = function (it) {
    if (!isObject(it)) {
      throw TypeError(String(it) + ' is not an object');
    } return it;
  };

  var nativeDefineProperty = Object.defineProperty;

  // `Object.defineProperty` method
  // https://tc39.es/ecma262/#sec-object.defineproperty
  var f$2 = descriptors ? nativeDefineProperty : function defineProperty(O, P, Attributes) {
    anObject(O);
    P = toPrimitive(P, true);
    anObject(Attributes);
    if (ie8DomDefine) try {
      return nativeDefineProperty(O, P, Attributes);
    } catch (error) { /* empty */ }
    if ('get' in Attributes || 'set' in Attributes) throw TypeError('Accessors not supported');
    if ('value' in Attributes) O[P] = Attributes.value;
    return O;
  };

  var objectDefineProperty = {
  	f: f$2
  };

  var createNonEnumerableProperty = descriptors ? function (object, key, value) {
    return objectDefineProperty.f(object, key, createPropertyDescriptor(1, value));
  } : function (object, key, value) {
    object[key] = value;
    return object;
  };

  var setGlobal = function (key, value) {
    try {
      createNonEnumerableProperty(global_1, key, value);
    } catch (error) {
      global_1[key] = value;
    } return value;
  };

  var SHARED = '__core-js_shared__';
  var store$1 = global_1[SHARED] || setGlobal(SHARED, {});

  var sharedStore = store$1;

  var functionToString = Function.toString;

  // this helper broken in `3.4.1-3.4.4`, so we can't use `shared` helper
  if (typeof sharedStore.inspectSource != 'function') {
    sharedStore.inspectSource = function (it) {
      return functionToString.call(it);
    };
  }

  var inspectSource = sharedStore.inspectSource;

  var WeakMap$1 = global_1.WeakMap;

  var nativeWeakMap = typeof WeakMap$1 === 'function' && /native code/.test(inspectSource(WeakMap$1));

  var shared = createCommonjsModule(function (module) {
  (module.exports = function (key, value) {
    return sharedStore[key] || (sharedStore[key] = value !== undefined ? value : {});
  })('versions', []).push({
    version: '3.9.1',
    mode: 'global',
    copyright: '© 2021 Denis Pushkarev (zloirock.ru)'
  });
  });

  var id = 0;
  var postfix = Math.random();

  var uid = function (key) {
    return 'Symbol(' + String(key === undefined ? '' : key) + ')_' + (++id + postfix).toString(36);
  };

  var keys = shared('keys');

  var sharedKey = function (key) {
    return keys[key] || (keys[key] = uid(key));
  };

  var hiddenKeys$1 = {};

  var WeakMap = global_1.WeakMap;
  var set, get, has;

  var enforce = function (it) {
    return has(it) ? get(it) : set(it, {});
  };

  var getterFor = function (TYPE) {
    return function (it) {
      var state;
      if (!isObject(it) || (state = get(it)).type !== TYPE) {
        throw TypeError('Incompatible receiver, ' + TYPE + ' required');
      } return state;
    };
  };

  if (nativeWeakMap) {
    var store = sharedStore.state || (sharedStore.state = new WeakMap());
    var wmget = store.get;
    var wmhas = store.has;
    var wmset = store.set;
    set = function (it, metadata) {
      metadata.facade = it;
      wmset.call(store, it, metadata);
      return metadata;
    };
    get = function (it) {
      return wmget.call(store, it) || {};
    };
    has = function (it) {
      return wmhas.call(store, it);
    };
  } else {
    var STATE = sharedKey('state');
    hiddenKeys$1[STATE] = true;
    set = function (it, metadata) {
      metadata.facade = it;
      createNonEnumerableProperty(it, STATE, metadata);
      return metadata;
    };
    get = function (it) {
      return has$1(it, STATE) ? it[STATE] : {};
    };
    has = function (it) {
      return has$1(it, STATE);
    };
  }

  var internalState = {
    set: set,
    get: get,
    has: has,
    enforce: enforce,
    getterFor: getterFor
  };

  var redefine = createCommonjsModule(function (module) {
  var getInternalState = internalState.get;
  var enforceInternalState = internalState.enforce;
  var TEMPLATE = String(String).split('String');

  (module.exports = function (O, key, value, options) {
    var unsafe = options ? !!options.unsafe : false;
    var simple = options ? !!options.enumerable : false;
    var noTargetGet = options ? !!options.noTargetGet : false;
    var state;
    if (typeof value == 'function') {
      if (typeof key == 'string' && !has$1(value, 'name')) {
        createNonEnumerableProperty(value, 'name', key);
      }
      state = enforceInternalState(value);
      if (!state.source) {
        state.source = TEMPLATE.join(typeof key == 'string' ? key : '');
      }
    }
    if (O === global_1) {
      if (simple) O[key] = value;
      else setGlobal(key, value);
      return;
    } else if (!unsafe) {
      delete O[key];
    } else if (!noTargetGet && O[key]) {
      simple = true;
    }
    if (simple) O[key] = value;
    else createNonEnumerableProperty(O, key, value);
  // add fake Function#toString for correct work wrapped methods / constructors with methods like LoDash isNative
  })(Function.prototype, 'toString', function toString() {
    return typeof this == 'function' && getInternalState(this).source || inspectSource(this);
  });
  });

  var path = global_1;

  var aFunction$1 = function (variable) {
    return typeof variable == 'function' ? variable : undefined;
  };

  var getBuiltIn = function (namespace, method) {
    return arguments.length < 2 ? aFunction$1(path[namespace]) || aFunction$1(global_1[namespace])
      : path[namespace] && path[namespace][method] || global_1[namespace] && global_1[namespace][method];
  };

  var ceil = Math.ceil;
  var floor$1 = Math.floor;

  // `ToInteger` abstract operation
  // https://tc39.es/ecma262/#sec-tointeger
  var toInteger = function (argument) {
    return isNaN(argument = +argument) ? 0 : (argument > 0 ? floor$1 : ceil)(argument);
  };

  var min$3 = Math.min;

  // `ToLength` abstract operation
  // https://tc39.es/ecma262/#sec-tolength
  var toLength = function (argument) {
    return argument > 0 ? min$3(toInteger(argument), 0x1FFFFFFFFFFFFF) : 0; // 2 ** 53 - 1 == 9007199254740991
  };

  var max$2 = Math.max;
  var min$2 = Math.min;

  // Helper for a popular repeating case of the spec:
  // Let integer be ? ToInteger(index).
  // If integer < 0, let result be max((length + integer), 0); else let result be min(integer, length).
  var toAbsoluteIndex = function (index, length) {
    var integer = toInteger(index);
    return integer < 0 ? max$2(integer + length, 0) : min$2(integer, length);
  };

  // `Array.prototype.{ indexOf, includes }` methods implementation
  var createMethod$2 = function (IS_INCLUDES) {
    return function ($this, el, fromIndex) {
      var O = toIndexedObject($this);
      var length = toLength(O.length);
      var index = toAbsoluteIndex(fromIndex, length);
      var value;
      // Array#includes uses SameValueZero equality algorithm
      // eslint-disable-next-line no-self-compare -- NaN check
      if (IS_INCLUDES && el != el) while (length > index) {
        value = O[index++];
        // eslint-disable-next-line no-self-compare -- NaN check
        if (value != value) return true;
      // Array#indexOf ignores holes, Array#includes - not
      } else for (;length > index; index++) {
        if ((IS_INCLUDES || index in O) && O[index] === el) return IS_INCLUDES || index || 0;
      } return !IS_INCLUDES && -1;
    };
  };

  var arrayIncludes = {
    // `Array.prototype.includes` method
    // https://tc39.es/ecma262/#sec-array.prototype.includes
    includes: createMethod$2(true),
    // `Array.prototype.indexOf` method
    // https://tc39.es/ecma262/#sec-array.prototype.indexof
    indexOf: createMethod$2(false)
  };

  var indexOf = arrayIncludes.indexOf;


  var objectKeysInternal = function (object, names) {
    var O = toIndexedObject(object);
    var i = 0;
    var result = [];
    var key;
    for (key in O) !has$1(hiddenKeys$1, key) && has$1(O, key) && result.push(key);
    // Don't enum bug & hidden keys
    while (names.length > i) if (has$1(O, key = names[i++])) {
      ~indexOf(result, key) || result.push(key);
    }
    return result;
  };

  // IE8- don't enum bug keys
  var enumBugKeys = [
    'constructor',
    'hasOwnProperty',
    'isPrototypeOf',
    'propertyIsEnumerable',
    'toLocaleString',
    'toString',
    'valueOf'
  ];

  var hiddenKeys = enumBugKeys.concat('length', 'prototype');

  // `Object.getOwnPropertyNames` method
  // https://tc39.es/ecma262/#sec-object.getownpropertynames
  var f$1 = Object.getOwnPropertyNames || function getOwnPropertyNames(O) {
    return objectKeysInternal(O, hiddenKeys);
  };

  var objectGetOwnPropertyNames = {
  	f: f$1
  };

  var f = Object.getOwnPropertySymbols;

  var objectGetOwnPropertySymbols = {
  	f: f
  };

  // all object keys, includes non-enumerable and symbols
  var ownKeys = getBuiltIn('Reflect', 'ownKeys') || function ownKeys(it) {
    var keys = objectGetOwnPropertyNames.f(anObject(it));
    var getOwnPropertySymbols = objectGetOwnPropertySymbols.f;
    return getOwnPropertySymbols ? keys.concat(getOwnPropertySymbols(it)) : keys;
  };

  var copyConstructorProperties = function (target, source) {
    var keys = ownKeys(source);
    var defineProperty = objectDefineProperty.f;
    var getOwnPropertyDescriptor = objectGetOwnPropertyDescriptor.f;
    for (var i = 0; i < keys.length; i++) {
      var key = keys[i];
      if (!has$1(target, key)) defineProperty(target, key, getOwnPropertyDescriptor(source, key));
    }
  };

  var replacement = /#|\.prototype\./;

  var isForced = function (feature, detection) {
    var value = data[normalize(feature)];
    return value == POLYFILL ? true
      : value == NATIVE ? false
      : typeof detection == 'function' ? fails(detection)
      : !!detection;
  };

  var normalize = isForced.normalize = function (string) {
    return String(string).replace(replacement, '.').toLowerCase();
  };

  var data = isForced.data = {};
  var NATIVE = isForced.NATIVE = 'N';
  var POLYFILL = isForced.POLYFILL = 'P';

  var isForced_1 = isForced;

  var getOwnPropertyDescriptor = objectGetOwnPropertyDescriptor.f;






  /*
    options.target      - name of the target object
    options.global      - target is the global object
    options.stat        - export as static methods of target
    options.proto       - export as prototype methods of target
    options.real        - real prototype method for the `pure` version
    options.forced      - export even if the native feature is available
    options.bind        - bind methods to the target, required for the `pure` version
    options.wrap        - wrap constructors to preventing global pollution, required for the `pure` version
    options.unsafe      - use the simple assignment of property instead of delete + defineProperty
    options.sham        - add a flag to not completely full polyfills
    options.enumerable  - export as enumerable property
    options.noTargetGet - prevent calling a getter on target
  */
  var _export = function (options, source) {
    var TARGET = options.target;
    var GLOBAL = options.global;
    var STATIC = options.stat;
    var FORCED, target, key, targetProperty, sourceProperty, descriptor;
    if (GLOBAL) {
      target = global_1;
    } else if (STATIC) {
      target = global_1[TARGET] || setGlobal(TARGET, {});
    } else {
      target = (global_1[TARGET] || {}).prototype;
    }
    if (target) for (key in source) {
      sourceProperty = source[key];
      if (options.noTargetGet) {
        descriptor = getOwnPropertyDescriptor(target, key);
        targetProperty = descriptor && descriptor.value;
      } else targetProperty = target[key];
      FORCED = isForced_1(GLOBAL ? key : TARGET + (STATIC ? '.' : '#') + key, options.forced);
      // contained in target
      if (!FORCED && targetProperty !== undefined) {
        if (typeof sourceProperty === typeof targetProperty) continue;
        copyConstructorProperties(sourceProperty, targetProperty);
      }
      // add a flag to not completely full polyfills
      if (options.sham || (targetProperty && targetProperty.sham)) {
        createNonEnumerableProperty(sourceProperty, 'sham', true);
      }
      // extend global
      redefine(target, key, sourceProperty, options);
    }
  };

  var aFunction = function (it) {
    if (typeof it != 'function') {
      throw TypeError(String(it) + ' is not a function');
    } return it;
  };

  // optional / simple context binding
  var functionBindContext = function (fn, that, length) {
    aFunction(fn);
    if (that === undefined) return fn;
    switch (length) {
      case 0: return function () {
        return fn.call(that);
      };
      case 1: return function (a) {
        return fn.call(that, a);
      };
      case 2: return function (a, b) {
        return fn.call(that, a, b);
      };
      case 3: return function (a, b, c) {
        return fn.call(that, a, b, c);
      };
    }
    return function (/* ...args */) {
      return fn.apply(that, arguments);
    };
  };

  // `ToObject` abstract operation
  // https://tc39.es/ecma262/#sec-toobject
  var toObject = function (argument) {
    return Object(requireObjectCoercible(argument));
  };

  // `IsArray` abstract operation
  // https://tc39.es/ecma262/#sec-isarray
  var isArray = Array.isArray || function isArray(arg) {
    return classofRaw(arg) == 'Array';
  };

  var engineIsNode = classofRaw(global_1.process) == 'process';

  var engineUserAgent = getBuiltIn('navigator', 'userAgent') || '';

  var process = global_1.process;
  var versions = process && process.versions;
  var v8 = versions && versions.v8;
  var match, version;

  if (v8) {
    match = v8.split('.');
    version = match[0] + match[1];
  } else if (engineUserAgent) {
    match = engineUserAgent.match(/Edge\/(\d+)/);
    if (!match || match[1] >= 74) {
      match = engineUserAgent.match(/Chrome\/(\d+)/);
      if (match) version = match[1];
    }
  }

  var engineV8Version = version && +version;

  var nativeSymbol = !!Object.getOwnPropertySymbols && !fails(function () {
    /* global Symbol -- required for testing */
    return !Symbol.sham &&
      // Chrome 38 Symbol has incorrect toString conversion
      // Chrome 38-40 symbols are not inherited from DOM collections prototypes to instances
      (engineIsNode ? engineV8Version === 38 : engineV8Version > 37 && engineV8Version < 41);
  });

  var useSymbolAsUid = nativeSymbol
    /* global Symbol -- safe */
    && !Symbol.sham
    && typeof Symbol.iterator == 'symbol';

  var WellKnownSymbolsStore = shared('wks');
  var Symbol$1 = global_1.Symbol;
  var createWellKnownSymbol = useSymbolAsUid ? Symbol$1 : Symbol$1 && Symbol$1.withoutSetter || uid;

  var wellKnownSymbol = function (name) {
    if (!has$1(WellKnownSymbolsStore, name) || !(nativeSymbol || typeof WellKnownSymbolsStore[name] == 'string')) {
      if (nativeSymbol && has$1(Symbol$1, name)) {
        WellKnownSymbolsStore[name] = Symbol$1[name];
      } else {
        WellKnownSymbolsStore[name] = createWellKnownSymbol('Symbol.' + name);
      }
    } return WellKnownSymbolsStore[name];
  };

  var SPECIES$4 = wellKnownSymbol('species');

  // `ArraySpeciesCreate` abstract operation
  // https://tc39.es/ecma262/#sec-arrayspeciescreate
  var arraySpeciesCreate = function (originalArray, length) {
    var C;
    if (isArray(originalArray)) {
      C = originalArray.constructor;
      // cross-realm fallback
      if (typeof C == 'function' && (C === Array || isArray(C.prototype))) C = undefined;
      else if (isObject(C)) {
        C = C[SPECIES$4];
        if (C === null) C = undefined;
      }
    } return new (C === undefined ? Array : C)(length === 0 ? 0 : length);
  };

  var push = [].push;

  // `Array.prototype.{ forEach, map, filter, some, every, find, findIndex, filterOut }` methods implementation
  var createMethod$1 = function (TYPE) {
    var IS_MAP = TYPE == 1;
    var IS_FILTER = TYPE == 2;
    var IS_SOME = TYPE == 3;
    var IS_EVERY = TYPE == 4;
    var IS_FIND_INDEX = TYPE == 6;
    var IS_FILTER_OUT = TYPE == 7;
    var NO_HOLES = TYPE == 5 || IS_FIND_INDEX;
    return function ($this, callbackfn, that, specificCreate) {
      var O = toObject($this);
      var self = indexedObject(O);
      var boundFunction = functionBindContext(callbackfn, that, 3);
      var length = toLength(self.length);
      var index = 0;
      var create = specificCreate || arraySpeciesCreate;
      var target = IS_MAP ? create($this, length) : IS_FILTER || IS_FILTER_OUT ? create($this, 0) : undefined;
      var value, result;
      for (;length > index; index++) if (NO_HOLES || index in self) {
        value = self[index];
        result = boundFunction(value, index, O);
        if (TYPE) {
          if (IS_MAP) target[index] = result; // map
          else if (result) switch (TYPE) {
            case 3: return true;              // some
            case 5: return value;             // find
            case 6: return index;             // findIndex
            case 2: push.call(target, value); // filter
          } else switch (TYPE) {
            case 4: return false;             // every
            case 7: push.call(target, value); // filterOut
          }
        }
      }
      return IS_FIND_INDEX ? -1 : IS_SOME || IS_EVERY ? IS_EVERY : target;
    };
  };

  var arrayIteration = {
    // `Array.prototype.forEach` method
    // https://tc39.es/ecma262/#sec-array.prototype.foreach
    forEach: createMethod$1(0),
    // `Array.prototype.map` method
    // https://tc39.es/ecma262/#sec-array.prototype.map
    map: createMethod$1(1),
    // `Array.prototype.filter` method
    // https://tc39.es/ecma262/#sec-array.prototype.filter
    filter: createMethod$1(2),
    // `Array.prototype.some` method
    // https://tc39.es/ecma262/#sec-array.prototype.some
    some: createMethod$1(3),
    // `Array.prototype.every` method
    // https://tc39.es/ecma262/#sec-array.prototype.every
    every: createMethod$1(4),
    // `Array.prototype.find` method
    // https://tc39.es/ecma262/#sec-array.prototype.find
    find: createMethod$1(5),
    // `Array.prototype.findIndex` method
    // https://tc39.es/ecma262/#sec-array.prototype.findIndex
    findIndex: createMethod$1(6),
    // `Array.prototype.filterOut` method
    // https://github.com/tc39/proposal-array-filtering
    filterOut: createMethod$1(7)
  };

  // `Object.keys` method
  // https://tc39.es/ecma262/#sec-object.keys
  var objectKeys = Object.keys || function keys(O) {
    return objectKeysInternal(O, enumBugKeys);
  };

  // `Object.defineProperties` method
  // https://tc39.es/ecma262/#sec-object.defineproperties
  var objectDefineProperties = descriptors ? Object.defineProperties : function defineProperties(O, Properties) {
    anObject(O);
    var keys = objectKeys(Properties);
    var length = keys.length;
    var index = 0;
    var key;
    while (length > index) objectDefineProperty.f(O, key = keys[index++], Properties[key]);
    return O;
  };

  var html = getBuiltIn('document', 'documentElement');

  var GT = '>';
  var LT = '<';
  var PROTOTYPE = 'prototype';
  var SCRIPT = 'script';
  var IE_PROTO = sharedKey('IE_PROTO');

  var EmptyConstructor = function () { /* empty */ };

  var scriptTag = function (content) {
    return LT + SCRIPT + GT + content + LT + '/' + SCRIPT + GT;
  };

  // Create object with fake `null` prototype: use ActiveX Object with cleared prototype
  var NullProtoObjectViaActiveX = function (activeXDocument) {
    activeXDocument.write(scriptTag(''));
    activeXDocument.close();
    var temp = activeXDocument.parentWindow.Object;
    activeXDocument = null; // avoid memory leak
    return temp;
  };

  // Create object with fake `null` prototype: use iframe Object with cleared prototype
  var NullProtoObjectViaIFrame = function () {
    // Thrash, waste and sodomy: IE GC bug
    var iframe = documentCreateElement('iframe');
    var JS = 'java' + SCRIPT + ':';
    var iframeDocument;
    iframe.style.display = 'none';
    html.appendChild(iframe);
    // https://github.com/zloirock/core-js/issues/475
    iframe.src = String(JS);
    iframeDocument = iframe.contentWindow.document;
    iframeDocument.open();
    iframeDocument.write(scriptTag('document.F=Object'));
    iframeDocument.close();
    return iframeDocument.F;
  };

  // Check for document.domain and active x support
  // No need to use active x approach when document.domain is not set
  // see https://github.com/es-shims/es5-shim/issues/150
  // variation of https://github.com/kitcambridge/es5-shim/commit/4f738ac066346
  // avoid IE GC bug
  var activeXDocument;
  var NullProtoObject = function () {
    try {
      /* global ActiveXObject -- old IE */
      activeXDocument = document.domain && new ActiveXObject('htmlfile');
    } catch (error) { /* ignore */ }
    NullProtoObject = activeXDocument ? NullProtoObjectViaActiveX(activeXDocument) : NullProtoObjectViaIFrame();
    var length = enumBugKeys.length;
    while (length--) delete NullProtoObject[PROTOTYPE][enumBugKeys[length]];
    return NullProtoObject();
  };

  hiddenKeys$1[IE_PROTO] = true;

  // `Object.create` method
  // https://tc39.es/ecma262/#sec-object.create
  var objectCreate = Object.create || function create(O, Properties) {
    var result;
    if (O !== null) {
      EmptyConstructor[PROTOTYPE] = anObject(O);
      result = new EmptyConstructor();
      EmptyConstructor[PROTOTYPE] = null;
      // add "__proto__" for Object.getPrototypeOf polyfill
      result[IE_PROTO] = O;
    } else result = NullProtoObject();
    return Properties === undefined ? result : objectDefineProperties(result, Properties);
  };

  var UNSCOPABLES = wellKnownSymbol('unscopables');
  var ArrayPrototype = Array.prototype;

  // Array.prototype[@@unscopables]
  // https://tc39.es/ecma262/#sec-array.prototype-@@unscopables
  if (ArrayPrototype[UNSCOPABLES] == undefined) {
    objectDefineProperty.f(ArrayPrototype, UNSCOPABLES, {
      configurable: true,
      value: objectCreate(null)
    });
  }

  // add a key to Array.prototype[@@unscopables]
  var addToUnscopables = function (key) {
    ArrayPrototype[UNSCOPABLES][key] = true;
  };

  var $find = arrayIteration.find;


  var FIND = 'find';
  var SKIPS_HOLES = true;

  // Shouldn't skip holes
  if (FIND in []) Array(1)[FIND](function () { SKIPS_HOLES = false; });

  // `Array.prototype.find` method
  // https://tc39.es/ecma262/#sec-array.prototype.find
  _export({ target: 'Array', proto: true, forced: SKIPS_HOLES }, {
    find: function find(callbackfn /* , that = undefined */) {
      return $find(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
    }
  });

  // https://tc39.es/ecma262/#sec-array.prototype-@@unscopables
  addToUnscopables(FIND);

  // `RegExp.prototype.flags` getter implementation
  // https://tc39.es/ecma262/#sec-get-regexp.prototype.flags
  var regexpFlags = function () {
    var that = anObject(this);
    var result = '';
    if (that.global) result += 'g';
    if (that.ignoreCase) result += 'i';
    if (that.multiline) result += 'm';
    if (that.dotAll) result += 's';
    if (that.unicode) result += 'u';
    if (that.sticky) result += 'y';
    return result;
  };

  // babel-minify transpiles RegExp('a', 'y') -> /a/y and it causes SyntaxError,
  // so we use an intermediate function.
  function RE(s, f) {
    return RegExp(s, f);
  }

  var UNSUPPORTED_Y$1 = fails(function () {
    // babel-minify transpiles RegExp('a', 'y') -> /a/y and it causes SyntaxError
    var re = RE('a', 'y');
    re.lastIndex = 2;
    return re.exec('abcd') != null;
  });

  var BROKEN_CARET = fails(function () {
    // https://bugzilla.mozilla.org/show_bug.cgi?id=773687
    var re = RE('^r', 'gy');
    re.lastIndex = 2;
    return re.exec('str') != null;
  });

  var regexpStickyHelpers = {
  	UNSUPPORTED_Y: UNSUPPORTED_Y$1,
  	BROKEN_CARET: BROKEN_CARET
  };

  var nativeExec = RegExp.prototype.exec;
  // This always refers to the native implementation, because the
  // String#replace polyfill uses ./fix-regexp-well-known-symbol-logic.js,
  // which loads this file before patching the method.
  var nativeReplace = String.prototype.replace;

  var patchedExec = nativeExec;

  var UPDATES_LAST_INDEX_WRONG = (function () {
    var re1 = /a/;
    var re2 = /b*/g;
    nativeExec.call(re1, 'a');
    nativeExec.call(re2, 'a');
    return re1.lastIndex !== 0 || re2.lastIndex !== 0;
  })();

  var UNSUPPORTED_Y = regexpStickyHelpers.UNSUPPORTED_Y || regexpStickyHelpers.BROKEN_CARET;

  // nonparticipating capturing group, copied from es5-shim's String#split patch.
  // eslint-disable-next-line regexp/no-assertion-capturing-group, regexp/no-empty-group -- required for testing
  var NPCG_INCLUDED = /()??/.exec('')[1] !== undefined;

  var PATCH = UPDATES_LAST_INDEX_WRONG || NPCG_INCLUDED || UNSUPPORTED_Y;

  if (PATCH) {
    patchedExec = function exec(str) {
      var re = this;
      var lastIndex, reCopy, match, i;
      var sticky = UNSUPPORTED_Y && re.sticky;
      var flags = regexpFlags.call(re);
      var source = re.source;
      var charsAdded = 0;
      var strCopy = str;

      if (sticky) {
        flags = flags.replace('y', '');
        if (flags.indexOf('g') === -1) {
          flags += 'g';
        }

        strCopy = String(str).slice(re.lastIndex);
        // Support anchored sticky behavior.
        if (re.lastIndex > 0 && (!re.multiline || re.multiline && str[re.lastIndex - 1] !== '\n')) {
          source = '(?: ' + source + ')';
          strCopy = ' ' + strCopy;
          charsAdded++;
        }
        // ^(? + rx + ) is needed, in combination with some str slicing, to
        // simulate the 'y' flag.
        reCopy = new RegExp('^(?:' + source + ')', flags);
      }

      if (NPCG_INCLUDED) {
        reCopy = new RegExp('^' + source + '$(?!\\s)', flags);
      }
      if (UPDATES_LAST_INDEX_WRONG) lastIndex = re.lastIndex;

      match = nativeExec.call(sticky ? reCopy : re, strCopy);

      if (sticky) {
        if (match) {
          match.input = match.input.slice(charsAdded);
          match[0] = match[0].slice(charsAdded);
          match.index = re.lastIndex;
          re.lastIndex += match[0].length;
        } else re.lastIndex = 0;
      } else if (UPDATES_LAST_INDEX_WRONG && match) {
        re.lastIndex = re.global ? match.index + match[0].length : lastIndex;
      }
      if (NPCG_INCLUDED && match && match.length > 1) {
        // Fix browsers whose `exec` methods don't consistently return `undefined`
        // for NPCG, like IE8. NOTE: This doesn' work for /(.?)?/
        nativeReplace.call(match[0], reCopy, function () {
          for (i = 1; i < arguments.length - 2; i++) {
            if (arguments[i] === undefined) match[i] = undefined;
          }
        });
      }

      return match;
    };
  }

  var regexpExec = patchedExec;

  // `RegExp.prototype.exec` method
  // https://tc39.es/ecma262/#sec-regexp.prototype.exec
  _export({ target: 'RegExp', proto: true, forced: /./.exec !== regexpExec }, {
    exec: regexpExec
  });

  // TODO: Remove from `core-js@4` since it's moved to entry points







  var SPECIES$3 = wellKnownSymbol('species');

  var REPLACE_SUPPORTS_NAMED_GROUPS = !fails(function () {
    // #replace needs built-in support for named groups.
    // #match works fine because it just return the exec results, even if it has
    // a "grops" property.
    var re = /./;
    re.exec = function () {
      var result = [];
      result.groups = { a: '7' };
      return result;
    };
    return ''.replace(re, '$<a>') !== '7';
  });

  // IE <= 11 replaces $0 with the whole match, as if it was $&
  // https://stackoverflow.com/questions/6024666/getting-ie-to-replace-a-regex-with-the-literal-string-0
  var REPLACE_KEEPS_$0 = (function () {
    return 'a'.replace(/./, '$0') === '$0';
  })();

  var REPLACE = wellKnownSymbol('replace');
  // Safari <= 13.0.3(?) substitutes nth capture where n>m with an empty string
  var REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE = (function () {
    if (/./[REPLACE]) {
      return /./[REPLACE]('a', '$0') === '';
    }
    return false;
  })();

  // Chrome 51 has a buggy "split" implementation when RegExp#exec !== nativeExec
  // Weex JS has frozen built-in prototypes, so use try / catch wrapper
  var SPLIT_WORKS_WITH_OVERWRITTEN_EXEC = !fails(function () {
    // eslint-disable-next-line regexp/no-empty-group -- required for testing
    var re = /(?:)/;
    var originalExec = re.exec;
    re.exec = function () { return originalExec.apply(this, arguments); };
    var result = 'ab'.split(re);
    return result.length !== 2 || result[0] !== 'a' || result[1] !== 'b';
  });

  var fixRegexpWellKnownSymbolLogic = function (KEY, length, exec, sham) {
    var SYMBOL = wellKnownSymbol(KEY);

    var DELEGATES_TO_SYMBOL = !fails(function () {
      // String methods call symbol-named RegEp methods
      var O = {};
      O[SYMBOL] = function () { return 7; };
      return ''[KEY](O) != 7;
    });

    var DELEGATES_TO_EXEC = DELEGATES_TO_SYMBOL && !fails(function () {
      // Symbol-named RegExp methods call .exec
      var execCalled = false;
      var re = /a/;

      if (KEY === 'split') {
        // We can't use real regex here since it causes deoptimization
        // and serious performance degradation in V8
        // https://github.com/zloirock/core-js/issues/306
        re = {};
        // RegExp[@@split] doesn't call the regex's exec method, but first creates
        // a new one. We need to return the patched regex when creating the new one.
        re.constructor = {};
        re.constructor[SPECIES$3] = function () { return re; };
        re.flags = '';
        re[SYMBOL] = /./[SYMBOL];
      }

      re.exec = function () { execCalled = true; return null; };

      re[SYMBOL]('');
      return !execCalled;
    });

    if (
      !DELEGATES_TO_SYMBOL ||
      !DELEGATES_TO_EXEC ||
      (KEY === 'replace' && !(
        REPLACE_SUPPORTS_NAMED_GROUPS &&
        REPLACE_KEEPS_$0 &&
        !REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE
      )) ||
      (KEY === 'split' && !SPLIT_WORKS_WITH_OVERWRITTEN_EXEC)
    ) {
      var nativeRegExpMethod = /./[SYMBOL];
      var methods = exec(SYMBOL, ''[KEY], function (nativeMethod, regexp, str, arg2, forceStringMethod) {
        if (regexp.exec === regexpExec) {
          if (DELEGATES_TO_SYMBOL && !forceStringMethod) {
            // The native String method already delegates to @@method (this
            // polyfilled function), leasing to infinite recursion.
            // We avoid it by directly calling the native @@method method.
            return { done: true, value: nativeRegExpMethod.call(regexp, str, arg2) };
          }
          return { done: true, value: nativeMethod.call(str, regexp, arg2) };
        }
        return { done: false };
      }, {
        REPLACE_KEEPS_$0: REPLACE_KEEPS_$0,
        REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE: REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE
      });
      var stringMethod = methods[0];
      var regexMethod = methods[1];

      redefine(String.prototype, KEY, stringMethod);
      redefine(RegExp.prototype, SYMBOL, length == 2
        // 21.2.5.8 RegExp.prototype[@@replace](string, replaceValue)
        // 21.2.5.11 RegExp.prototype[@@split](string, limit)
        ? function (string, arg) { return regexMethod.call(string, this, arg); }
        // 21.2.5.6 RegExp.prototype[@@match](string)
        // 21.2.5.9 RegExp.prototype[@@search](string)
        : function (string) { return regexMethod.call(string, this); }
      );
    }

    if (sham) createNonEnumerableProperty(RegExp.prototype[SYMBOL], 'sham', true);
  };

  var MATCH = wellKnownSymbol('match');

  // `IsRegExp` abstract operation
  // https://tc39.es/ecma262/#sec-isregexp
  var isRegexp = function (it) {
    var isRegExp;
    return isObject(it) && ((isRegExp = it[MATCH]) !== undefined ? !!isRegExp : classofRaw(it) == 'RegExp');
  };

  var SPECIES$2 = wellKnownSymbol('species');

  // `SpeciesConstructor` abstract operation
  // https://tc39.es/ecma262/#sec-speciesconstructor
  var speciesConstructor = function (O, defaultConstructor) {
    var C = anObject(O).constructor;
    var S;
    return C === undefined || (S = anObject(C)[SPECIES$2]) == undefined ? defaultConstructor : aFunction(S);
  };

  // `String.prototype.{ codePointAt, at }` methods implementation
  var createMethod = function (CONVERT_TO_STRING) {
    return function ($this, pos) {
      var S = String(requireObjectCoercible($this));
      var position = toInteger(pos);
      var size = S.length;
      var first, second;
      if (position < 0 || position >= size) return CONVERT_TO_STRING ? '' : undefined;
      first = S.charCodeAt(position);
      return first < 0xD800 || first > 0xDBFF || position + 1 === size
        || (second = S.charCodeAt(position + 1)) < 0xDC00 || second > 0xDFFF
          ? CONVERT_TO_STRING ? S.charAt(position) : first
          : CONVERT_TO_STRING ? S.slice(position, position + 2) : (first - 0xD800 << 10) + (second - 0xDC00) + 0x10000;
    };
  };

  var stringMultibyte = {
    // `String.prototype.codePointAt` method
    // https://tc39.es/ecma262/#sec-string.prototype.codepointat
    codeAt: createMethod(false),
    // `String.prototype.at` method
    // https://github.com/mathiasbynens/String.prototype.at
    charAt: createMethod(true)
  };

  var charAt = stringMultibyte.charAt;

  // `AdvanceStringIndex` abstract operation
  // https://tc39.es/ecma262/#sec-advancestringindex
  var advanceStringIndex = function (S, index, unicode) {
    return index + (unicode ? charAt(S, index).length : 1);
  };

  // `RegExpExec` abstract operation
  // https://tc39.es/ecma262/#sec-regexpexec
  var regexpExecAbstract = function (R, S) {
    var exec = R.exec;
    if (typeof exec === 'function') {
      var result = exec.call(R, S);
      if (typeof result !== 'object') {
        throw TypeError('RegExp exec method returned something other than an Object or null');
      }
      return result;
    }

    if (classofRaw(R) !== 'RegExp') {
      throw TypeError('RegExp#exec called on incompatible receiver');
    }

    return regexpExec.call(R, S);
  };

  var arrayPush = [].push;
  var min$1 = Math.min;
  var MAX_UINT32 = 0xFFFFFFFF;

  // babel-minify transpiles RegExp('x', 'y') -> /x/y and it causes SyntaxError
  var SUPPORTS_Y = !fails(function () { return !RegExp(MAX_UINT32, 'y'); });

  // @@split logic
  fixRegexpWellKnownSymbolLogic('split', 2, function (SPLIT, nativeSplit, maybeCallNative) {
    var internalSplit;
    if (
      'abbc'.split(/(b)*/)[1] == 'c' ||
      // eslint-disable-next-line regexp/no-empty-group -- required for testing
      'test'.split(/(?:)/, -1).length != 4 ||
      'ab'.split(/(?:ab)*/).length != 2 ||
      '.'.split(/(.?)(.?)/).length != 4 ||
      // eslint-disable-next-line regexp/no-assertion-capturing-group, regexp/no-empty-group -- required for testing
      '.'.split(/()()/).length > 1 ||
      ''.split(/.?/).length
    ) {
      // based on es5-shim implementation, need to rework it
      internalSplit = function (separator, limit) {
        var string = String(requireObjectCoercible(this));
        var lim = limit === undefined ? MAX_UINT32 : limit >>> 0;
        if (lim === 0) return [];
        if (separator === undefined) return [string];
        // If `separator` is not a regex, use native split
        if (!isRegexp(separator)) {
          return nativeSplit.call(string, separator, lim);
        }
        var output = [];
        var flags = (separator.ignoreCase ? 'i' : '') +
                    (separator.multiline ? 'm' : '') +
                    (separator.unicode ? 'u' : '') +
                    (separator.sticky ? 'y' : '');
        var lastLastIndex = 0;
        // Make `global` and avoid `lastIndex` issues by working with a copy
        var separatorCopy = new RegExp(separator.source, flags + 'g');
        var match, lastIndex, lastLength;
        while (match = regexpExec.call(separatorCopy, string)) {
          lastIndex = separatorCopy.lastIndex;
          if (lastIndex > lastLastIndex) {
            output.push(string.slice(lastLastIndex, match.index));
            if (match.length > 1 && match.index < string.length) arrayPush.apply(output, match.slice(1));
            lastLength = match[0].length;
            lastLastIndex = lastIndex;
            if (output.length >= lim) break;
          }
          if (separatorCopy.lastIndex === match.index) separatorCopy.lastIndex++; // Avoid an infinite loop
        }
        if (lastLastIndex === string.length) {
          if (lastLength || !separatorCopy.test('')) output.push('');
        } else output.push(string.slice(lastLastIndex));
        return output.length > lim ? output.slice(0, lim) : output;
      };
    // Chakra, V8
    } else if ('0'.split(undefined, 0).length) {
      internalSplit = function (separator, limit) {
        return separator === undefined && limit === 0 ? [] : nativeSplit.call(this, separator, limit);
      };
    } else internalSplit = nativeSplit;

    return [
      // `String.prototype.split` method
      // https://tc39.es/ecma262/#sec-string.prototype.split
      function split(separator, limit) {
        var O = requireObjectCoercible(this);
        var splitter = separator == undefined ? undefined : separator[SPLIT];
        return splitter !== undefined
          ? splitter.call(separator, O, limit)
          : internalSplit.call(String(O), separator, limit);
      },
      // `RegExp.prototype[@@split]` method
      // https://tc39.es/ecma262/#sec-regexp.prototype-@@split
      //
      // NOTE: This cannot be properly polyfilled in engines that don't support
      // the 'y' flag.
      function (regexp, limit) {
        var res = maybeCallNative(internalSplit, regexp, this, limit, internalSplit !== nativeSplit);
        if (res.done) return res.value;

        var rx = anObject(regexp);
        var S = String(this);
        var C = speciesConstructor(rx, RegExp);

        var unicodeMatching = rx.unicode;
        var flags = (rx.ignoreCase ? 'i' : '') +
                    (rx.multiline ? 'm' : '') +
                    (rx.unicode ? 'u' : '') +
                    (SUPPORTS_Y ? 'y' : 'g');

        // ^(? + rx + ) is needed, in combination with some S slicing, to
        // simulate the 'y' flag.
        var splitter = new C(SUPPORTS_Y ? rx : '^(?:' + rx.source + ')', flags);
        var lim = limit === undefined ? MAX_UINT32 : limit >>> 0;
        if (lim === 0) return [];
        if (S.length === 0) return regexpExecAbstract(splitter, S) === null ? [S] : [];
        var p = 0;
        var q = 0;
        var A = [];
        while (q < S.length) {
          splitter.lastIndex = SUPPORTS_Y ? q : 0;
          var z = regexpExecAbstract(splitter, SUPPORTS_Y ? S : S.slice(q));
          var e;
          if (
            z === null ||
            (e = min$1(toLength(splitter.lastIndex + (SUPPORTS_Y ? 0 : q)), S.length)) === p
          ) {
            q = advanceStringIndex(S, q, unicodeMatching);
          } else {
            A.push(S.slice(p, q));
            if (A.length === lim) return A;
            for (var i = 1; i <= z.length - 1; i++) {
              A.push(z[i]);
              if (A.length === lim) return A;
            }
            q = p = e;
          }
        }
        A.push(S.slice(p));
        return A;
      }
    ];
  }, !SUPPORTS_Y);

  var floor = Math.floor;
  var replace = ''.replace;
  var SUBSTITUTION_SYMBOLS = /\$([$&'`]|\d{1,2}|<[^>]*>)/g;
  var SUBSTITUTION_SYMBOLS_NO_NAMED = /\$([$&'`]|\d{1,2})/g;

  // https://tc39.es/ecma262/#sec-getsubstitution
  var getSubstitution = function (matched, str, position, captures, namedCaptures, replacement) {
    var tailPos = position + matched.length;
    var m = captures.length;
    var symbols = SUBSTITUTION_SYMBOLS_NO_NAMED;
    if (namedCaptures !== undefined) {
      namedCaptures = toObject(namedCaptures);
      symbols = SUBSTITUTION_SYMBOLS;
    }
    return replace.call(replacement, symbols, function (match, ch) {
      var capture;
      switch (ch.charAt(0)) {
        case '$': return '$';
        case '&': return matched;
        case '`': return str.slice(0, position);
        case "'": return str.slice(tailPos);
        case '<':
          capture = namedCaptures[ch.slice(1, -1)];
          break;
        default: // \d\d?
          var n = +ch;
          if (n === 0) return match;
          if (n > m) {
            var f = floor(n / 10);
            if (f === 0) return match;
            if (f <= m) return captures[f - 1] === undefined ? ch.charAt(1) : captures[f - 1] + ch.charAt(1);
            return match;
          }
          capture = captures[n - 1];
      }
      return capture === undefined ? '' : capture;
    });
  };

  var max$1 = Math.max;
  var min = Math.min;

  var maybeToString = function (it) {
    return it === undefined ? it : String(it);
  };

  // @@replace logic
  fixRegexpWellKnownSymbolLogic('replace', 2, function (REPLACE, nativeReplace, maybeCallNative, reason) {
    var REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE = reason.REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE;
    var REPLACE_KEEPS_$0 = reason.REPLACE_KEEPS_$0;
    var UNSAFE_SUBSTITUTE = REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE ? '$' : '$0';

    return [
      // `String.prototype.replace` method
      // https://tc39.es/ecma262/#sec-string.prototype.replace
      function replace(searchValue, replaceValue) {
        var O = requireObjectCoercible(this);
        var replacer = searchValue == undefined ? undefined : searchValue[REPLACE];
        return replacer !== undefined
          ? replacer.call(searchValue, O, replaceValue)
          : nativeReplace.call(String(O), searchValue, replaceValue);
      },
      // `RegExp.prototype[@@replace]` method
      // https://tc39.es/ecma262/#sec-regexp.prototype-@@replace
      function (regexp, replaceValue) {
        if (
          (!REGEXP_REPLACE_SUBSTITUTES_UNDEFINED_CAPTURE && REPLACE_KEEPS_$0) ||
          (typeof replaceValue === 'string' && replaceValue.indexOf(UNSAFE_SUBSTITUTE) === -1)
        ) {
          var res = maybeCallNative(nativeReplace, regexp, this, replaceValue);
          if (res.done) return res.value;
        }

        var rx = anObject(regexp);
        var S = String(this);

        var functionalReplace = typeof replaceValue === 'function';
        if (!functionalReplace) replaceValue = String(replaceValue);

        var global = rx.global;
        if (global) {
          var fullUnicode = rx.unicode;
          rx.lastIndex = 0;
        }
        var results = [];
        while (true) {
          var result = regexpExecAbstract(rx, S);
          if (result === null) break;

          results.push(result);
          if (!global) break;

          var matchStr = String(result[0]);
          if (matchStr === '') rx.lastIndex = advanceStringIndex(S, toLength(rx.lastIndex), fullUnicode);
        }

        var accumulatedResult = '';
        var nextSourcePosition = 0;
        for (var i = 0; i < results.length; i++) {
          result = results[i];

          var matched = String(result[0]);
          var position = max$1(min(toInteger(result.index), S.length), 0);
          var captures = [];
          // NOTE: This is equivalent to
          //   captures = result.slice(1).map(maybeToString)
          // but for some reason `nativeSlice.call(result, 1, result.length)` (called in
          // the slice polyfill when slicing native arrays) "doesn't work" in safari 9 and
          // causes a crash (https://pastebin.com/N21QzeQA) when trying to debug it.
          for (var j = 1; j < result.length; j++) captures.push(maybeToString(result[j]));
          var namedCaptures = result.groups;
          if (functionalReplace) {
            var replacerArgs = [matched].concat(captures, position, S);
            if (namedCaptures !== undefined) replacerArgs.push(namedCaptures);
            var replacement = String(replaceValue.apply(undefined, replacerArgs));
          } else {
            replacement = getSubstitution(matched, S, position, captures, namedCaptures, replaceValue);
          }
          if (position >= nextSourcePosition) {
            accumulatedResult += S.slice(nextSourcePosition, position) + replacement;
            nextSourcePosition = position + matched.length;
          }
        }
        return accumulatedResult + S.slice(nextSourcePosition);
      }
    ];
  });

  var createProperty = function (object, key, value) {
    var propertyKey = toPrimitive(key);
    if (propertyKey in object) objectDefineProperty.f(object, propertyKey, createPropertyDescriptor(0, value));
    else object[propertyKey] = value;
  };

  var SPECIES$1 = wellKnownSymbol('species');

  var arrayMethodHasSpeciesSupport = function (METHOD_NAME) {
    // We can't use this feature detection in V8 since it causes
    // deoptimization and serious performance degradation
    // https://github.com/zloirock/core-js/issues/677
    return engineV8Version >= 51 || !fails(function () {
      var array = [];
      var constructor = array.constructor = {};
      constructor[SPECIES$1] = function () {
        return { foo: 1 };
      };
      return array[METHOD_NAME](Boolean).foo !== 1;
    });
  };

  var HAS_SPECIES_SUPPORT$1 = arrayMethodHasSpeciesSupport('slice');

  var SPECIES = wellKnownSymbol('species');
  var nativeSlice = [].slice;
  var max = Math.max;

  // `Array.prototype.slice` method
  // https://tc39.es/ecma262/#sec-array.prototype.slice
  // fallback for not array-like ES3 strings and DOM objects
  _export({ target: 'Array', proto: true, forced: !HAS_SPECIES_SUPPORT$1 }, {
    slice: function slice(start, end) {
      var O = toIndexedObject(this);
      var length = toLength(O.length);
      var k = toAbsoluteIndex(start, length);
      var fin = toAbsoluteIndex(end === undefined ? length : end, length);
      // inline `ArraySpeciesCreate` for usage native `Array#slice` where it's possible
      var Constructor, result, n;
      if (isArray(O)) {
        Constructor = O.constructor;
        // cross-realm fallback
        if (typeof Constructor == 'function' && (Constructor === Array || isArray(Constructor.prototype))) {
          Constructor = undefined;
        } else if (isObject(Constructor)) {
          Constructor = Constructor[SPECIES];
          if (Constructor === null) Constructor = undefined;
        }
        if (Constructor === Array || Constructor === undefined) {
          return nativeSlice.call(O, k, fin);
        }
      }
      result = new (Constructor === undefined ? Array : Constructor)(max(fin - k, 0));
      for (n = 0; k < fin; k++, n++) if (k in O) createProperty(result, n, O[k]);
      result.length = n;
      return result;
    }
  });

  var $map = arrayIteration.map;


  var HAS_SPECIES_SUPPORT = arrayMethodHasSpeciesSupport('map');

  // `Array.prototype.map` method
  // https://tc39.es/ecma262/#sec-array.prototype.map
  // with adding support of @@species
  _export({ target: 'Array', proto: true, forced: !HAS_SPECIES_SUPPORT }, {
    map: function map(callbackfn /* , thisArg */) {
      return $map(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
    }
  });

  var nativeAssign = Object.assign;
  var defineProperty = Object.defineProperty;

  // `Object.assign` method
  // https://tc39.es/ecma262/#sec-object.assign
  var objectAssign = !nativeAssign || fails(function () {
    // should have correct order of operations (Edge bug)
    if (descriptors && nativeAssign({ b: 1 }, nativeAssign(defineProperty({}, 'a', {
      enumerable: true,
      get: function () {
        defineProperty(this, 'b', {
          value: 3,
          enumerable: false
        });
      }
    }), { b: 2 })).b !== 1) return true;
    // should work with symbols and should have deterministic property order (V8 bug)
    var A = {};
    var B = {};
    /* global Symbol -- required for testing */
    var symbol = Symbol();
    var alphabet = 'abcdefghijklmnopqrst';
    A[symbol] = 7;
    alphabet.split('').forEach(function (chr) { B[chr] = chr; });
    return nativeAssign({}, A)[symbol] != 7 || objectKeys(nativeAssign({}, B)).join('') != alphabet;
  }) ? function assign(target, source) { // eslint-disable-line no-unused-vars -- required for `.length`
    var T = toObject(target);
    var argumentsLength = arguments.length;
    var index = 1;
    var getOwnPropertySymbols = objectGetOwnPropertySymbols.f;
    var propertyIsEnumerable = objectPropertyIsEnumerable.f;
    while (argumentsLength > index) {
      var S = indexedObject(arguments[index++]);
      var keys = getOwnPropertySymbols ? objectKeys(S).concat(getOwnPropertySymbols(S)) : objectKeys(S);
      var length = keys.length;
      var j = 0;
      var key;
      while (length > j) {
        key = keys[j++];
        if (!descriptors || propertyIsEnumerable.call(S, key)) T[key] = S[key];
      }
    } return T;
  } : nativeAssign;

  // `Object.assign` method
  // https://tc39.es/ecma262/#sec-object.assign
  _export({ target: 'Object', stat: true, forced: Object.assign !== objectAssign }, {
    assign: objectAssign
  });

  var IS_CONCAT_SPREADABLE = wellKnownSymbol('isConcatSpreadable');
  var MAX_SAFE_INTEGER = 0x1FFFFFFFFFFFFF;
  var MAXIMUM_ALLOWED_INDEX_EXCEEDED = 'Maximum allowed index exceeded';

  // We can't use this feature detection in V8 since it causes
  // deoptimization and serious performance degradation
  // https://github.com/zloirock/core-js/issues/679
  var IS_CONCAT_SPREADABLE_SUPPORT = engineV8Version >= 51 || !fails(function () {
    var array = [];
    array[IS_CONCAT_SPREADABLE] = false;
    return array.concat()[0] !== array;
  });

  var SPECIES_SUPPORT = arrayMethodHasSpeciesSupport('concat');

  var isConcatSpreadable = function (O) {
    if (!isObject(O)) return false;
    var spreadable = O[IS_CONCAT_SPREADABLE];
    return spreadable !== undefined ? !!spreadable : isArray(O);
  };

  var FORCED = !IS_CONCAT_SPREADABLE_SUPPORT || !SPECIES_SUPPORT;

  // `Array.prototype.concat` method
  // https://tc39.es/ecma262/#sec-array.prototype.concat
  // with adding support of @@isConcatSpreadable and @@species
  _export({ target: 'Array', proto: true, forced: FORCED }, {
    // eslint-disable-next-line no-unused-vars -- required for `.length`
    concat: function concat(arg) {
      var O = toObject(this);
      var A = arraySpeciesCreate(O, 0);
      var n = 0;
      var i, k, length, len, E;
      for (i = -1, length = arguments.length; i < length; i++) {
        E = i === -1 ? O : arguments[i];
        if (isConcatSpreadable(E)) {
          len = toLength(E.length);
          if (n + len > MAX_SAFE_INTEGER) throw TypeError(MAXIMUM_ALLOWED_INDEX_EXCEEDED);
          for (k = 0; k < len; k++, n++) if (k in E) createProperty(A, n, E[k]);
        } else {
          if (n >= MAX_SAFE_INTEGER) throw TypeError(MAXIMUM_ALLOWED_INDEX_EXCEEDED);
          createProperty(A, n++, E);
        }
      }
      A.length = n;
      return A;
    }
  });

  var arrayMethodIsStrict = function (METHOD_NAME, argument) {
    var method = [][METHOD_NAME];
    return !!method && fails(function () {
      // eslint-disable-next-line no-useless-call,no-throw-literal -- required for testing
      method.call(null, argument || function () { throw 1; }, 1);
    });
  };

  var nativeJoin = [].join;

  var ES3_STRINGS = indexedObject != Object;
  var STRICT_METHOD$1 = arrayMethodIsStrict('join', ',');

  // `Array.prototype.join` method
  // https://tc39.es/ecma262/#sec-array.prototype.join
  _export({ target: 'Array', proto: true, forced: ES3_STRINGS || !STRICT_METHOD$1 }, {
    join: function join(separator) {
      return nativeJoin.call(toIndexedObject(this), separator === undefined ? ',' : separator);
    }
  });

  // iterable DOM collections
  // flag - `iterable` interface - 'entries', 'keys', 'values', 'forEach' methods
  var domIterables = {
    CSSRuleList: 0,
    CSSStyleDeclaration: 0,
    CSSValueList: 0,
    ClientRectList: 0,
    DOMRectList: 0,
    DOMStringList: 0,
    DOMTokenList: 1,
    DataTransferItemList: 0,
    FileList: 0,
    HTMLAllCollection: 0,
    HTMLCollection: 0,
    HTMLFormElement: 0,
    HTMLSelectElement: 0,
    MediaList: 0,
    MimeTypeArray: 0,
    NamedNodeMap: 0,
    NodeList: 1,
    PaintRequestList: 0,
    Plugin: 0,
    PluginArray: 0,
    SVGLengthList: 0,
    SVGNumberList: 0,
    SVGPathSegList: 0,
    SVGPointList: 0,
    SVGStringList: 0,
    SVGTransformList: 0,
    SourceBufferList: 0,
    StyleSheetList: 0,
    TextTrackCueList: 0,
    TextTrackList: 0,
    TouchList: 0
  };

  var $forEach = arrayIteration.forEach;


  var STRICT_METHOD = arrayMethodIsStrict('forEach');

  // `Array.prototype.forEach` method implementation
  // https://tc39.es/ecma262/#sec-array.prototype.foreach
  var arrayForEach = !STRICT_METHOD ? function forEach(callbackfn /* , thisArg */) {
    return $forEach(this, callbackfn, arguments.length > 1 ? arguments[1] : undefined);
  } : [].forEach;

  for (var COLLECTION_NAME in domIterables) {
    var Collection = global_1[COLLECTION_NAME];
    var CollectionPrototype = Collection && Collection.prototype;
    // some Chrome versions have non-configurable methods on DOMTokenList
    if (CollectionPrototype && CollectionPrototype.forEach !== arrayForEach) try {
      createNonEnumerableProperty(CollectionPrototype, 'forEach', arrayForEach);
    } catch (error) {
      CollectionPrototype.forEach = arrayForEach;
    }
  }

  /**
   * @author zhixin wen <wenzhixin2010@gmail.com>
   * extensions: https://github.com/hhurz/tableExport.jquery.plugin
   */

  var Utils = $__default['default'].fn.bootstrapTable.utils;
  var TYPE_NAME = {
    json: 'JSON',
    xml: 'XML',
    png: 'PNG',
    csv: 'CSV',
    txt: 'TXT',
    sql: 'SQL',
    doc: 'MS-Word',
    excel: 'MS-Excel',
    xlsx: 'MS-Excel (OpenXML)',
    powerpoint: 'MS-Powerpoint',
    pdf: 'PDF'
  };
  $__default['default'].extend($__default['default'].fn.bootstrapTable.defaults, {
    showExport: false,
    exportDataType: 'basic',
    // basic, all, selected
    exportTypes: ['json', 'xml', 'csv', 'txt', 'sql', 'excel'],
    exportOptions: {
      onCellHtmlData: function onCellHtmlData(cell, rowIndex, colIndex, htmlData) {
        if (cell.is('th')) {
          return cell.find('.th-inner').text();
        }

        return htmlData;
      }
    },
    exportFooter: false
  });
  $__default['default'].extend($__default['default'].fn.bootstrapTable.columnDefaults, {
    forceExport: false,
    forceHide: false
  });
  $__default['default'].extend($__default['default'].fn.bootstrapTable.defaults.icons, {
    export: {
      bootstrap3: 'fa fa-download icon-share',
      materialize: 'file_download',
      'bootstrap-table': 'icon-download'
    }[$__default['default'].fn.bootstrapTable.theme] || 'fa-download'
  });
  $__default['default'].extend($__default['default'].fn.bootstrapTable.locales, {
    formatExport: function formatExport() {
      return 'Export data';
    }
  });
  $__default['default'].extend($__default['default'].fn.bootstrapTable.defaults, $__default['default'].fn.bootstrapTable.locales);
  $__default['default'].fn.bootstrapTable.methods.push('exportTable');
  $__default['default'].extend($__default['default'].fn.bootstrapTable.defaults, {
    // eslint-disable-next-line no-unused-vars
    onExportSaved: function onExportSaved(exportedRows) {
      return false;
    }
  });
  $__default['default'].extend($__default['default'].fn.bootstrapTable.Constructor.EVENTS, {
    'export-saved.bs.table': 'onExportSaved'
  });

  $__default['default'].BootstrapTable = /*#__PURE__*/function (_$$BootstrapTable) {
    _inherits(_class, _$$BootstrapTable);

    var _super = _createSuper(_class);

    function _class() {
      _classCallCheck(this, _class);

      return _super.apply(this, arguments);
    }

    _createClass(_class, [{
      key: "initToolbar",
      value: function initToolbar() {
        var _get2,
            _this = this;

        var o = this.options;
        var exportTypes = o.exportTypes;
        this.showToolbar = this.showToolbar || o.showExport;

        if (this.options.showExport) {
          if (typeof exportTypes === 'string') {
            var types = exportTypes.slice(1, -1).replace(/ /g, '').split(',');
            exportTypes = types.map(function (t) {
              return t.slice(1, -1);
            });
          }

          this.$export = this.$toolbar.find('>.columns div.export');

          if (this.$export.length) {
            this.updateExportButton();
            return;
          }

          this.buttons = Object.assign(this.buttons, {
            export: {
              html: exportTypes.length === 1 ? "\n            <div class=\"export ".concat(this.constants.classes.buttonsDropdown, "\"\n            data-type=\"").concat(exportTypes[0], "\">\n            <button class=\"").concat(this.constants.buttonsClass, "\"\n            aria-label=\"Export\"\n            type=\"button\"\n            title=\"").concat(o.formatExport(), "\">\n            ").concat(o.showButtonIcons ? Utils.sprintf(this.constants.html.icon, o.iconsPrefix, o.icons.export) : '', "\n            ").concat(o.showButtonText ? o.formatExport() : '', "\n            </button>\n            </div>\n          ") : "\n            <div class=\"export ".concat(this.constants.classes.buttonsDropdown, "\">\n            <button class=\"").concat(this.constants.buttonsClass, " dropdown-toggle\"\n            aria-label=\"Export\"\n            ").concat(this.constants.dataToggle, "=\"dropdown\"\n            type=\"button\"\n            title=\"").concat(o.formatExport(), "\">\n            ").concat(o.showButtonIcons ? Utils.sprintf(this.constants.html.icon, o.iconsPrefix, o.icons.export) : '', "\n            ").concat(o.showButtonText ? o.formatExport() : '', "\n            ").concat(this.constants.html.dropdownCaret, "\n            </button>\n            </div>\n          ")
            }
          });
        }

        for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
          args[_key] = arguments[_key];
        }

        (_get2 = _get(_getPrototypeOf(_class.prototype), "initToolbar", this)).call.apply(_get2, [this].concat(args));

        this.$export = this.$toolbar.find('>.columns div.export');

        if (!this.options.showExport) {
          return;
        }

        var $menu = $__default['default'](this.constants.html.toolbarDropdown.join(''));
        var $items = this.$export;

        if (exportTypes.length > 1) {
          this.$export.append($menu); // themes support

          if ($menu.children().length) {
            $menu = $menu.children().eq(0);
          }

          var _iterator = _createForOfIteratorHelper(exportTypes),
              _step;

          try {
            for (_iterator.s(); !(_step = _iterator.n()).done;) {
              var type = _step.value;

              if (TYPE_NAME.hasOwnProperty(type)) {
                var $item = $__default['default'](Utils.sprintf(this.constants.html.pageDropdownItem, '', TYPE_NAME[type]));
                $item.attr('data-type', type);
                $menu.append($item);
              }
            }
          } catch (err) {
            _iterator.e(err);
          } finally {
            _iterator.f();
          }

          $items = $menu.children();
        }

        this.updateExportButton();
        $items.click(function (e) {
          e.preventDefault();
          var type = $__default['default'](e.currentTarget).data('type');
          var exportOptions = {
            type: type,
            escape: false
          };

          _this.exportTable(exportOptions);
        });
        this.handleToolbar();
      }
    }, {
      key: "handleToolbar",
      value: function handleToolbar() {
        if (!this.$export) {
          return;
        }

        if (_get(_getPrototypeOf(_class.prototype), "handleToolbar", this)) {
          _get(_getPrototypeOf(_class.prototype), "handleToolbar", this).call(this);
        }
      }
    }, {
      key: "exportTable",
      value: function exportTable(options) {
        var _this2 = this;

        var o = this.options;
        var stateField = this.header.stateField;
        var isCardView = o.cardView;

        var doExport = function doExport(callback) {
          if (stateField) {
            _this2.hideColumn(stateField);
          }

          if (isCardView) {
            _this2.toggleView();
          }

          _this2.columns.forEach(function (row) {
            if (row.forceHide) {
              _this2.hideColumn(row.field);
            }
          });

          var data = _this2.getData();

          if (o.detailView && o.detailViewIcon) {
            var detailViewIndex = o.detailViewAlign === 'left' ? 0 : _this2.getVisibleFields().length + Utils.getDetailViewIndexOffset(_this2.options);
            o.exportOptions.ignoreColumn = [detailViewIndex].concat(o.exportOptions.ignoreColumn || []);
          }

          if (o.exportFooter) {
            var $footerRow = _this2.$tableFooter.find('tr').first();

            var footerData = {};
            var footerHtml = [];
            $__default['default'].each($footerRow.children(), function (index, footerCell) {
              var footerCellHtml = $__default['default'](footerCell).children('.th-inner').first().html();
              footerData[_this2.columns[index].field] = footerCellHtml === '&nbsp;' ? null : footerCellHtml; // grab footer cell text into cell index-based array

              footerHtml.push(footerCellHtml);
            });

            _this2.$body.append(_this2.$body.children().last()[0].outerHTML);

            var $lastTableRow = _this2.$body.children().last();

            $__default['default'].each($lastTableRow.children(), function (index, lastTableRowCell) {
              $__default['default'](lastTableRowCell).html(footerHtml[index]);
            });
          }

          var hiddenColumns = _this2.getHiddenColumns();

          hiddenColumns.forEach(function (row) {
            if (row.forceExport) {
              _this2.showColumn(row.field);
            }
          });

          if (typeof o.exportOptions.fileName === 'function') {
            options.fileName = o.exportOptions.fileName();
          }

          _this2.$el.tableExport($__default['default'].extend({
            onAfterSaveToFile: function onAfterSaveToFile() {
              if (o.exportFooter) {
                _this2.load(data);
              }

              if (stateField) {
                _this2.showColumn(stateField);
              }

              if (isCardView) {
                _this2.toggleView();
              }

              hiddenColumns.forEach(function (row) {
                if (row.forceExport) {
                  _this2.hideColumn(row.field);
                }
              });

              _this2.columns.forEach(function (row) {
                if (row.forceHide) {
                  _this2.showColumn(row.field);
                }
              });

              if (callback) callback();
            }
          }, o.exportOptions, options));
        };

        if (o.exportDataType === 'all' && o.pagination) {
          var eventName = o.sidePagination === 'server' ? 'post-body.bs.table' : 'page-change.bs.table';
          var virtualScroll = this.options.virtualScroll;
          this.$el.one(eventName, function () {
            setTimeout(function () {
              doExport(function () {
                _this2.options.virtualScroll = virtualScroll;

                _this2.togglePagination();
              });
            }, 0);
          });
          this.options.virtualScroll = false;
          this.togglePagination();
          this.trigger('export-saved', this.getData());
        } else if (o.exportDataType === 'selected') {
          var data = this.getData();
          var selectedData = this.getSelections();
          var pagination = o.pagination;

          if (!selectedData.length) {
            return;
          }

          if (o.sidePagination === 'server') {
            data = _defineProperty({
              total: o.totalRows
            }, this.options.dataField, data);
            selectedData = _defineProperty({
              total: selectedData.length
            }, this.options.dataField, selectedData);
          }

          this.load(selectedData);

          if (pagination) {
            this.togglePagination();
          }

          doExport(function () {
            if (pagination) {
              _this2.togglePagination();
            }

            _this2.load(data);
          });
          this.trigger('export-saved', selectedData);
        } else {
          doExport();
          this.trigger('export-saved', this.getData(true));
        }
      }
    }, {
      key: "updateSelected",
      value: function updateSelected() {
        _get(_getPrototypeOf(_class.prototype), "updateSelected", this).call(this);

        this.updateExportButton();
      }
    }, {
      key: "updateExportButton",
      value: function updateExportButton() {
        if (this.options.exportDataType === 'selected') {
          this.$export.find('> button').prop('disabled', !this.getSelections().length);
        }
      }
    }]);

    return _class;
  }($__default['default'].BootstrapTable);

})));