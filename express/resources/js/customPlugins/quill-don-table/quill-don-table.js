/**
 * Quill Don Table Module
 * 
 * A custom table module for Quill editor that supports:
 * - Creating tables with customizable rows and columns
 * - Tab navigation between cells
 * - Adding/removing rows and columns
 * - Basic table formatting
 * 
 * Usage:
 * 1. Import and register the module
 * 2. Add 'table: {}' to modules in Quill options
 * 3. Use tableModule.insertTable(rows, cols) to create tables
 * 4. Use Tab/Shift+Tab to navigate between cells
 */

import Quill from 'quill';

const Block = Quill.import('blots/block');
const Container = Quill.import('blots/container');
const Parchment = Quill.import('parchment');

// Table Blot (outermost container)
class TableBlot extends Container {
  static create() {
    const node = super.create();
    node.classList.add('don-table');
    return node;
  }
}

TableBlot.blotName = 'table';
TableBlot.tagName = 'TABLE';

// Table Body Blot
class TableBodyBlot extends Container {
}

TableBodyBlot.blotName = 'table-body';
TableBodyBlot.tagName = 'TBODY';

// Table Row Blot
class TableRowBlot extends Container {
}

TableRowBlot.blotName = 'table-row';
TableRowBlot.tagName = 'TR';

// Table Cell Blot
class TableCellBlot extends Block {
  static create(value) {
    const node = super.create();
    const { colspan = 1, rowspan = 1 } = value || {};
    
    if (colspan > 1) {
      node.setAttribute('colspan', colspan);
    }
    if (rowspan > 1) {
      node.setAttribute('rowspan', rowspan);
    }
    
    return node;
  }

  static formats(domNode) {
    const colspan = domNode.getAttribute('colspan');
    const rowspan = domNode.getAttribute('rowspan');
    return {
      colspan: colspan ? parseInt(colspan) : 1,
      rowspan: rowspan ? parseInt(rowspan) : 1
    };
  }

  format(name, value) {
    if (name === 'colspan' || name === 'rowspan') {
      if (value > 1) {
        this.domNode.setAttribute(name, value);
      } else {
        this.domNode.removeAttribute(name);
      }
    } else {
      super.format(name, value);
    }
  }
}

TableCellBlot.blotName = 'table-cell';
TableCellBlot.tagName = 'TD';

// Set up the container hierarchy
TableBlot.allowedChildren = [TableBodyBlot];
TableBodyBlot.allowedChildren = [TableRowBlot];
TableRowBlot.allowedChildren = [TableCellBlot];

// Table Module
class TableModule {
  constructor(quill, options = {}) {
    this.quill = quill;
    this.options = {
      operationMenu: {},
      ...options
    };
    
    this.tableId = 1;
    this.bindEvents();
  }

  bindEvents() {
    // Handle backspace and delete in table cells
    this.quill.keyboard.addBinding({
      key: 'Backspace'
    }, (range, context) => {
      if (range.length === 0) {
        const [cell] = this.quill.getLine(range.index);
        if (cell && cell.statics.blotName === 'table-cell') {
          const cellLength = cell.length();
          if (range.index === cell.offset(this.quill.scroll) + cellLength - 1) {
            return false; // Prevent deletion of cell structure
          }
        }
      }
      return true;
    });

    // Handle tab navigation
    this.quill.keyboard.addBinding({
      key: 'Tab'
    }, (range, context) => {
      const [cell] = this.quill.getLine(range.index);
      if (cell && cell.statics.blotName === 'table-cell') {
        const nextCell = this.getNextCell(cell);
        if (nextCell) {
          this.quill.setSelection(nextCell.offset(this.quill.scroll), 0);
          return false;
        }
      }
      return true;
    });

    // Handle shift+tab navigation
    this.quill.keyboard.addBinding({
      key: 'Tab',
      shiftKey: true
    }, (range, context) => {
      const [cell] = this.quill.getLine(range.index);
      if (cell && cell.statics.blotName === 'table-cell') {
        const prevCell = this.getPrevCell(cell);
        if (prevCell) {
          this.quill.setSelection(prevCell.offset(this.quill.scroll), 0);
          return false;
        }
      }
      return true;
    });
  }

  insertTable(rows = 3, cols = 3) {
    const range = this.quill.getSelection();
    if (!range) return;

    // Clear any selected content
    if (range.length > 0) {
      this.quill.deleteText(range.index, range.length);
    }

    // Create table HTML with proper structure for Quill
    let tableHTML = '<table class="don-table"><tbody>';
    
    for (let i = 0; i < rows; i++) {
      tableHTML += '<tr>';
      for (let j = 0; j < cols; j++) {
        tableHTML += '<td><p><br></p></td>'; // Use <p> tags that Quill understands
      }
      tableHTML += '</tr>';
    }
    
    tableHTML += '</tbody></table>';

    // Use Quill's clipboard to convert and insert the HTML properly
    const clipboard = this.quill.clipboard;
    const delta = clipboard.convert(tableHTML);
    
    // Insert the delta at the current position
    this.quill.updateContents(
      this.quill.getContents().slice(0, range.index)
        .concat(delta)
        .concat(this.quill.getContents().slice(range.index))
    );
    
    // Add a paragraph after for continued editing
    const newParagraph = clipboard.convert('<p><br></p>');
    this.quill.updateContents(
      this.quill.getContents().concat(newParagraph)
    );
    
    console.log('Table inserted successfully');
  }

  getTable(cell) {
    let blot = cell;
    while (blot && blot.statics.blotName !== 'table') {
      blot = blot.parent;
    }
    return blot;
  }

  getRow(cell) {
    let blot = cell;
    while (blot && blot.statics.blotName !== 'table-row') {
      blot = blot.parent;
    }
    return blot;
  }

  getNextCell(cell) {
    const row = this.getRow(cell);
    if (!row) return null;

    const cellIndex = row.children.indexOf(cell);
    if (cellIndex < row.children.length - 1) {
      return row.children.at(cellIndex + 1);
    }

    // Move to next row
    const table = this.getTable(cell);
    if (!table) return null;

    const tbody = table.children.head;
    const rowIndex = tbody.children.indexOf(row);
    if (rowIndex < tbody.children.length - 1) {
      const nextRow = tbody.children.at(rowIndex + 1);
      return nextRow.children.head;
    }

    return null;
  }

  getPrevCell(cell) {
    const row = this.getRow(cell);
    if (!row) return null;

    const cellIndex = row.children.indexOf(cell);
    if (cellIndex > 0) {
      return row.children.at(cellIndex - 1);
    }

    // Move to previous row
    const table = this.getTable(cell);
    if (!table) return null;

    const tbody = table.children.head;
    const rowIndex = tbody.children.indexOf(row);
    if (rowIndex > 0) {
      const prevRow = tbody.children.at(rowIndex - 1);
      return prevRow.children.tail;
    }

    return null;
  }

  addRow(cell, direction = 'after') {
    const row = this.getRow(cell);
    const table = this.getTable(cell);
    if (!row || !table) return;

    const tbody = table.children.head;
    const rowIndex = tbody.children.indexOf(row);
    const colCount = row.children.length;

    // Create new row
    const newRowDelta = [];
    for (let i = 0; i < colCount; i++) {
      newRowDelta.push({
        insert: '\n',
        attributes: { 'table-cell': { colspan: 1, rowspan: 1 } }
      });
    }

    // Insert the row
    const insertIndex = direction === 'after' ? 
      row.offset(this.quill.scroll) + row.length() :
      row.offset(this.quill.scroll);

    let index = insertIndex;
    newRowDelta.forEach(cellDelta => {
      this.quill.insertText(index, cellDelta.insert, cellDelta.attributes);
      index += cellDelta.insert.length;
    });
  }

  addColumn(cell, direction = 'after') {
    const table = this.getTable(cell);
    const row = this.getRow(cell);
    if (!table || !row) return;

    const tbody = table.children.head;
    const cellIndex = row.children.indexOf(cell);
    const insertCellIndex = direction === 'after' ? cellIndex + 1 : cellIndex;

    // Add cell to each row
    tbody.children.forEach(tableRow => {
      const newCellDelta = {
        insert: '\n',
        attributes: { 'table-cell': { colspan: 1, rowspan: 1 } }
      };

      if (insertCellIndex >= tableRow.children.length) {
        // Add to end
        const lastCell = tableRow.children.tail;
        const insertIndex = lastCell.offset(this.quill.scroll) + lastCell.length();
        this.quill.insertText(insertIndex, newCellDelta.insert, newCellDelta.attributes);
      } else {
        // Insert at position
        const targetCell = tableRow.children.at(insertCellIndex);
        const insertIndex = targetCell.offset(this.quill.scroll);
        this.quill.insertText(insertIndex, newCellDelta.insert, newCellDelta.attributes);
      }
    });
  }

  deleteRow(cell) {
    const row = this.getRow(cell);
    const table = this.getTable(cell);
    if (!row || !table) return;

    const tbody = table.children.head;
    if (tbody.children.length <= 1) {
      // Delete entire table if only one row
      this.deleteTable(cell);
      return;
    }

    // Delete the row
    const rowStart = row.offset(this.quill.scroll);
    const rowLength = row.length();
    this.quill.deleteText(rowStart, rowLength);
  }

  deleteColumn(cell) {
    const table = this.getTable(cell);
    const row = this.getRow(cell);
    if (!table || !row) return;

    const tbody = table.children.head;
    const cellIndex = row.children.indexOf(cell);
    
    if (row.children.length <= 1) {
      // Delete entire table if only one column
      this.deleteTable(cell);
      return;
    }

    // Delete cell from each row
    tbody.children.forEach(tableRow => {
      if (cellIndex < tableRow.children.length) {
        const cellToDelete = tableRow.children.at(cellIndex);
        const cellStart = cellToDelete.offset(this.quill.scroll);
        const cellLength = cellToDelete.length();
        this.quill.deleteText(cellStart, cellLength);
      }
    });
  }

  deleteTable(cell) {
    const table = this.getTable(cell);
    if (!table) return;

    const tableStart = table.offset(this.quill.scroll);
    const tableLength = table.length();
    this.quill.deleteText(tableStart, tableLength);
  }
}

// Export everything but don't register here - registration will happen in TE3.vue

export { TableModule, TableBlot, TableBodyBlot, TableRowBlot, TableCellBlot };
export default TableModule;
