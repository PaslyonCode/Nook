(function () {
  'use strict';

  class ResizableImageTool {
    static get toolbox() {
      return {
        title: 'Image',
        icon: '<svg width="17" height="15" viewBox="0 0 17 15" xmlns="http://www.w3.org/2000/svg"><path d="M15.75 0H1.25C.56 0 0 .56 0 1.25v12.5C0 14.44.56 15 1.25 15h14.5c.69 0 1.25-.56 1.25-1.25V1.25C17 .56 16.44 0 15.75 0ZM15.5 13.5h-14v-12h14v12ZM5.25 5.5A1.75 1.75 0 1 0 5.25 2a1.75 1.75 0 0 0 0 3.5Zm8.95 6H2.8l3.25-4.07 2.2 2.65 2.05-2.45 3.9 3.87Z"/></svg>'
      };
    }

    static get isReadOnlySupported() { return true; }

    constructor({ data, config, readOnly }) {
      this.data = data || {};
      this.config = config || {};
      this.readOnly = !!readOnly;
      this.file = this.data.file || (this.data.url ? { url: this.data.url } : { url: '' });
      this.caption = this.data.caption || '';
      this.width = this.normalizeWidth(this.data.width || this.data.imageWidth || '100%');
      this.wrapper = null;
      this.image = null;
      this.captionInput = null;
    }

    normalizeWidth(value) {
      const raw = String(value || '').trim();
      if (!raw) return '100%';
      if (/^\d{1,3}%$/.test(raw)) {
        const n = Math.min(100, Math.max(10, parseInt(raw, 10)));
        return n + '%';
      }
      if (/^\d+(\.\d+)?px$/.test(raw)) return raw;
      return '100%';
    }

    render() {
      this.wrapper = document.createElement('div');
      this.wrapper.className = 'resizable-image-tool';

      const preview = document.createElement('div');
      preview.className = 'resizable-image-tool__preview';
      this.wrapper.appendChild(preview);

      this.image = document.createElement('img');
      this.image.className = 'resizable-image-tool__image';
      this.image.alt = '';
      preview.appendChild(this.image);

      const empty = document.createElement('div');
      empty.className = 'resizable-image-tool__empty';
      empty.textContent = 'No image selected';
      preview.appendChild(empty);

      const controls = document.createElement('div');
      controls.className = 'resizable-image-tool__controls';
      this.wrapper.appendChild(controls);

      if (!this.readOnly) {
        const uploadBtn = document.createElement('button');
        uploadBtn.type = 'button';
        uploadBtn.className = 'resizable-image-tool__btn';
        uploadBtn.textContent = this.file.url ? 'Replace' : 'Choose image';

        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = 'image/*';
        fileInput.hidden = true;

        uploadBtn.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', async () => {
          const file = fileInput.files && fileInput.files[0];
          if (!file) return;
          uploadBtn.disabled = true;
          uploadBtn.textContent = 'Uploading...';
          try {
            await this.uploadFile(file);
          } catch (error) {
            alert(error.message || 'Could not upload image');
          } finally {
            uploadBtn.disabled = false;
            uploadBtn.textContent = this.file.url ? 'Replace' : 'Choose image';
            fileInput.value = '';
          }
        });

        controls.appendChild(uploadBtn);
        controls.appendChild(fileInput);
      }

      const sizes = ['25%', '50%', '75%', '100%'];
      const sizeBox = document.createElement('div');
      sizeBox.className = 'resizable-image-tool__sizes';
      sizes.forEach((size) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'resizable-image-tool__btn resizable-image-tool__size-btn';
        btn.textContent = size;
        btn.disabled = this.readOnly;
        btn.dataset.size = size;
        btn.addEventListener('click', () => {
          this.width = size;
          this.applyImageState();
        });
        sizeBox.appendChild(btn);
      });
      controls.appendChild(sizeBox);

      this.captionInput = document.createElement('input');
      this.captionInput.className = 'resizable-image-tool__caption';
      this.captionInput.placeholder = this.config.captionPlaceholder || 'Image caption';
      this.captionInput.value = this.caption;
      this.captionInput.readOnly = this.readOnly;
      this.wrapper.appendChild(this.captionInput);

      this.applyImageState();
      return this.wrapper;
    }

    async uploadFile(file) {
      if (!this.config.uploader || typeof this.config.uploader.uploadByFile !== 'function') {
        throw new Error('Image uploader is not configured');
      }
      const result = await this.config.uploader.uploadByFile(file);
      if (!result || result.success !== 1 || !result.file || !result.file.url) {
        throw new Error('The server did not return an image URL');
      }
      this.file = { url: result.file.url };
      this.applyImageState();
    }

    applyImageState() {
      if (!this.wrapper || !this.image) return;
      const hasImage = !!(this.file && this.file.url);
      this.wrapper.classList.toggle('resizable-image-tool--empty', !hasImage);
      this.image.hidden = !hasImage;
      if (hasImage) {
        this.image.src = this.file.url;
        this.image.style.width = this.width;
        this.image.style.maxWidth = '100%';
      }
      const buttons = Array.from(this.wrapper.querySelectorAll('.resizable-image-tool__size-btn'));
      buttons.forEach((btn) => btn.classList.toggle('is-active', btn.dataset.size === this.width));
    }

    save() {
      return {
        file: this.file || { url: '' },
        caption: this.captionInput ? this.captionInput.value : this.caption,
        width: this.width,
        withBorder: false,
        withBackground: false,
        stretched: false
      };
    }
  }

  window.ResizableImageTool = ResizableImageTool;
})();
