// customModules/vminFontSize.js
const VminFontSize = {
  init(quill, options) {
    this.quill = quill;
    this.options = options;
    this.addFontSizeControls();
  },

  addFontSizeControls() {
    const toolbar = this.quill.getModule('toolbar');
    if (!toolbar) return;

    // Add custom font size handler
    toolbar.addHandler('size', (value) => {
      this.quill.format('size', value);
    });

    // Replace default size dropdown
    const sizePicker = toolbar.container.querySelector('.ql-size');
    if (sizePicker) {
      sizePicker.innerHTML = `
        <select>
          <option value="">Size</option>
          <option value="2vmin">2vmin</option>
          <option value="2.5vmin">2.5vmin</option>
          <option value="3vmin">3vmin</option>
          <option value="3.5vmin">3.5vmin</option>
          <option value="4vmin">4vmin</option>
          <option value="5vmin">5vmin</option>
        </select>
      `;
      sizePicker.querySelector('select').addEventListener('change', (e) => {
        const value = e.target.value;
        if (value) {
          toolbar.handlers.size.call(toolbar, value);
        } else {
          this.quill.format('size', false); // Remove formatting
        }
      });
    }
  }
};

export default VminFontSize;