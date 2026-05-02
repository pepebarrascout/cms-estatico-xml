/**
 * CMS Estático XML - Admin Panel JavaScript
 */

(function() {
    'use strict';

    // ============================================
    // Sidebar Toggle (Mobile)
    // ============================================
    const sidebar = document.querySelector('.admin-sidebar');
    const toggle = document.querySelector('.sidebar-toggle');
    const overlay = document.querySelector('.sidebar-overlay');

    if (toggle && sidebar) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function() {
            if (sidebar) sidebar.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }

    // ============================================
    // Form Validation Helpers
    // ============================================
    window.AdminForm = {
        /**
         * Validate a form field
         */
        validateField(input, rules) {
            const value = input.value.trim();
            const errors = [];

            if (rules.required && !value) {
                errors.push('Este campo es obligatorio');
            }

            if (rules.minLength && value.length < rules.minLength) {
                errors.push('Mínimo ' + rules.minLength + ' caracteres');
            }

            if (rules.maxLength && value.length > rules.maxLength) {
                errors.push('Máximo ' + rules.maxLength + ' caracteres');
            }

            if (rules.email && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                errors.push('Correo electrónico no válido');
            }

            if (rules.pattern && value && !rules.pattern.test(value)) {
                errors.push(rules.patternMessage || 'Formato no válido');
            }

            // Show/hide error
            const errorEl = input.parentElement.querySelector('.field-error');
            if (errorEl) {
                errorEl.textContent = errors.length ? errors[0] : '';
                errorEl.style.display = errors.length ? 'block' : 'none';
            }

            input.classList.toggle('is-invalid', errors.length > 0);
            input.classList.toggle('is-valid', errors.length === 0 && value.length > 0);

            return errors.length === 0;
        },

        /**
         * Validate an entire form
         */
        validateForm(formElement) {
            let isValid = true;
            const fields = formElement.querySelectorAll('[data-validate]');

            fields.forEach(function(input) {
                const rules = JSON.parse(input.dataset.validate || '{}');
                if (!AdminForm.validateField(input, rules)) {
                    isValid = false;
                }
            });

            return isValid;
        },

        /**
         * Sanitize string for display
         */
        sanitize(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    };

    // ============================================
    // Category Management (Article Edit Page)
    // ============================================
    window.CategoryManager = {
        categories: [],

        init(selectedCategories) {
            this.categories = selectedCategories || [];
            this.renderTags();
        },

        add(slug, name) {
            if (!slug || this.categories.find(c => c.slug === slug)) return;
            this.categories.push({ slug: slug, name: name });

            // Update hidden input
            const hiddenInput = document.getElementById('categories-input');
            if (hiddenInput) {
                hiddenInput.value = this.categories.map(c => c.slug).join(',');
            }

            this.renderTags();
        },

        remove(slug) {
            this.categories = this.categories.filter(c => c.slug !== slug);

            const hiddenInput = document.getElementById('categories-input');
            if (hiddenInput) {
                hiddenInput.value = this.categories.map(c => c.slug).join(',');
            }

            this.renderTags();
        },

        renderTags() {
            const container = document.getElementById('category-tags');
            if (!container) return;

            container.innerHTML = '';
            this.categories.forEach(function(cat) {
                const tag = document.createElement('span');
                tag.className = 'category-tag';
                tag.innerHTML = AdminForm.sanitize(cat.name) +
                    ' <span class="remove-tag" data-slug="' + AdminForm.sanitize(cat.slug) + '" title="Eliminar">&times;</span>';
                container.appendChild(tag);
            });

            // Bind remove buttons
            container.querySelectorAll('.remove-tag').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    CategoryManager.remove(this.dataset.slug);
                });
            });
        },

        /**
         * Create new category via API
         */
        async createNew(inputEl) {
            const name = inputEl.value.trim();
            if (!name) return;

            const btn = inputEl.parentElement.querySelector('.btn');
            const originalText = btn ? btn.innerHTML : '';

            if (btn) btn.innerHTML = '<span class="spinner"></span>';

            try {
                const response = await fetch('/admin/api/create-category', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: name, description: '' })
                });

                const data = await response.json();

                if (data.success) {
                    CategoryManager.add(data.slug, data.name);
                    inputEl.value = '';

                    // Update multi-select if exists
                    const select = document.getElementById('category-select');
                    if (select) {
                        const option = document.createElement('option');
                        option.value = data.slug;
                        option.textContent = data.name;
                        select.appendChild(option);
                        option.selected = true;
                    }

                    showNotification('Categoría "' + data.name + '" creada', 'success');
                } else {
                    showNotification(data.message || 'Error al crear categoría', 'error');
                }
            } catch (err) {
                showNotification('Error de conexión', 'error');
            }

            if (btn) btn.innerHTML = originalText;
        }
    };

    // ============================================
    // Markdown Preview Toggle
    // ============================================
    window.MarkdownPreview = {
        editorVisible: true,
        easyMDEInstance: null,

        init() {
            const toggleBtn = document.getElementById('toggle-preview');
            if (!toggleBtn) return;

            toggleBtn.addEventListener('click', function() {
                MarkdownPreview.toggle();
            });
        },

        setEasyMDE(instance) {
            this.easyMDEInstance = instance;
        },

        toggle() {
            const editorArea = document.getElementById('editor-area');
            const previewArea = document.getElementById('preview-area');
            const toggleBtn = document.getElementById('toggle-preview');

            if (!editorArea || !previewArea) return;

            this.editorVisible = !this.editorVisible;

            if (this.editorVisible) {
                editorArea.classList.remove('hidden');
                previewArea.classList.add('hidden');
                if (toggleBtn) toggleBtn.textContent = '👁️ Vista previa';
            } else {
                this.loadPreview();
                editorArea.classList.add('hidden');
                previewArea.classList.remove('hidden');
                if (toggleBtn) toggleBtn.textContent = '✏️ Editor';
            }
        },

        async loadPreview() {
            const previewContent = document.getElementById('preview-content');
            if (!previewContent) return;

            let markdown = '';
            if (this.easyMDEInstance) {
                markdown = this.easyMDEInstance.value();
            } else {
                const textarea = document.getElementById('content-editor');
                if (textarea) markdown = textarea.value;
            }

            previewContent.innerHTML = '<div class="text-center" style="padding:40px"><span class="spinner"></span> Cargando vista previa...</div>';

            try {
                const response = await fetch('/admin/api/preview', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ content: markdown })
                });

                const data = await response.json();
                if (data.html) {
                    previewContent.innerHTML = data.html;
                } else {
                    previewContent.innerHTML = '<p class="text-muted text-center">No se pudo generar la vista previa</p>';
                }
            } catch (err) {
                previewContent.innerHTML = '<p class="text-muted text-center">Error de conexión</p>';
            }
        }
    };

    // ============================================
    // Scheduled Date Visibility
    // ============================================
    function initScheduledField() {
        const statusRadios = document.querySelectorAll('input[name="status"]');
        const scheduledGroup = document.getElementById('scheduled-group');

        if (!statusRadios.length || !scheduledGroup) return;

        function toggleScheduled() {
            const selected = document.querySelector('input[name="status"]:checked');
            if (selected && selected.value === 'scheduled') {
                scheduledGroup.classList.remove('hidden');
            } else {
                scheduledGroup.classList.add('hidden');
            }
        }

        statusRadios.forEach(function(radio) {
            radio.addEventListener('change', toggleScheduled);
        });

        toggleScheduled();

        // Set default datetime
        const input = scheduledGroup.querySelector('input[type="datetime-local"]');
        if (input && !input.value) {
            const now = new Date();
            now.setHours(now.getHours() + 1);
            const yyyy = now.getFullYear();
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            const dd = String(now.getDate()).padStart(2, '0');
            const hh = String(now.getHours()).padStart(2, '0');
            const mi = String(now.getMinutes()).padStart(2, '0');
            input.value = yyyy + '-' + mm + '-' + dd + 'T' + hh + ':' + mi;
        }
    }

    // ============================================
    // Confirmation Dialog
    // ============================================
    window.confirmAction = function(message) {
        return new Promise(function(resolve) {
            const overlay = document.createElement('div');
            overlay.className = 'confirm-overlay';
            overlay.innerHTML =
                '<div class="confirm-dialog">' +
                '<h3>Confirmar acción</h3>' +
                '<p>' + AdminForm.sanitize(message) + '</p>' +
                '<div class="confirm-actions">' +
                '<button class="btn btn-secondary" id="confirm-cancel">Cancelar</button>' +
                '<button class="btn btn-danger" id="confirm-ok">Eliminar</button>' +
                '</div></div>';

            document.body.appendChild(overlay);

            overlay.querySelector('#confirm-cancel').addEventListener('click', function() {
                document.body.removeChild(overlay);
                resolve(false);
            });

            overlay.querySelector('#confirm-ok').addEventListener('click', function() {
                document.body.removeChild(overlay);
                resolve(true);
            });

            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    document.body.removeChild(overlay);
                    resolve(false);
                }
            });
        });
    };

    // ============================================
    // Delete Buttons (POST form with confirmation)
    // ============================================
    document.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('[data-delete]');
        if (!deleteBtn) return;

        e.preventDefault();

        const message = deleteBtn.dataset.delete || '¿Estás seguro de que deseas eliminar este elemento?';
        const formAction = deleteBtn.dataset.action || deleteBtn.closest('form')?.action;
        const slug = deleteBtn.dataset.slug;

        confirmAction(message).then(function(confirmed) {
            if (!confirmed) return;

            // Create and submit a hidden form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = formAction || window.location.href;

            const fields = deleteBtn.dataset.fields ? JSON.parse(deleteBtn.dataset.fields) : {};
            fields.action = 'delete';
            if (slug) fields.slug = slug;

            Object.keys(fields).forEach(function(key) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = fields[key];
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
        });
    });

    // ============================================
    // OTP Auto-focus Inputs
    // ============================================
    function initOTPInputs() {
        const inputs = document.querySelectorAll('.otp-input');
        if (!inputs.length) return;

        inputs.forEach(function(input, index) {
            input.addEventListener('input', function(e) {
                const value = e.target.value.replace(/[^0-9]/g, '');
                e.target.value = value.slice(-1);

                if (value && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });

            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const data = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
                data.split('').forEach(function(char, i) {
                    if (inputs[index + i]) {
                        inputs[index + i].value = char;
                    }
                });
                const nextIndex = Math.min(index + data.length, inputs.length - 1);
                inputs[nextIndex].focus();
            });
        });
    }

    // ============================================
    // Notification System
    // ============================================
    window.showNotification = function(message, type) {
        type = type || 'info';
        const existing = document.querySelector('.flash-message.notification');
        if (existing) existing.remove();

        const el = document.createElement('div');
        el.className = 'flash-message ' + type + ' notification';

        const icons = { success: '✅', error: '❌', info: 'ℹ️' };
        el.innerHTML = (icons[type] || '') + ' ' + AdminForm.sanitize(message);

        const content = document.querySelector('.admin-content') || document.querySelector('.admin-topbar')?.parentElement;
        if (content) {
            content.insertBefore(el, content.firstChild);
        }

        setTimeout(function() {
            el.style.opacity = '0';
            el.style.transition = 'opacity 0.3s ease';
            setTimeout(function() { el.remove(); }, 300);
        }, 4000);
    };

    // ============================================
    // Auto-save Draft (Skeleton)
    // ============================================
    window.AutoSave = {
        timer: null,
        interval: 30000, // 30 seconds

        init() {
            const form = document.getElementById('article-form');
            if (!form) return;

            form.addEventListener('input', function() {
                AutoSave.scheduleSave();
            });
        },

        scheduleSave() {
            clearTimeout(this.timer);
            this.timer = setTimeout(function() {
                AutoSave.saveDraft();
            }, this.interval);
        },

        saveDraft() {
            const form = document.getElementById('article-form');
            if (!form) return;

            const btn = document.getElementById('autosave-indicator');
            if (btn) btn.textContent = 'Guardando...';

            // Skeleton: would POST to an autosave endpoint
            // For now, save to localStorage
            try {
                const data = {
                    title: form.querySelector('[name="title"]')?.value || '',
                    content: form.querySelector('[name="content"]')?.value || '',
                    meta_description: form.querySelector('[name="meta_description"]')?.value || '',
                    savedAt: new Date().toISOString()
                };
                localStorage.setItem('cms_draft', JSON.stringify(data));

                if (btn) btn.textContent = 'Borrador guardado ' + new Date().toLocaleTimeString();
                setTimeout(function() {
                    if (btn) btn.textContent = '';
                }, 3000);
            } catch (e) {
                // localStorage not available
            }
        },

        loadDraft() {
            try {
                const data = JSON.parse(localStorage.getItem('cms_draft') || '{}');
                return data;
            } catch (e) {
                return {};
            }
        },

        clearDraft() {
            try {
                localStorage.removeItem('cms_draft');
            } catch (e) {}
        }
    };

    // ============================================
    // Multi-select to Category Manager Bridge
    // ============================================
    function initCategorySelect() {
        const select = document.getElementById('category-select');
        if (!select) return;

        select.addEventListener('change', function() {
            const selected = Array.from(select.selectedOptions).map(function(opt) {
                return { slug: opt.value, name: opt.textContent };
            });

            // Clear and re-add
            CategoryManager.categories = [];
            selected.forEach(function(cat) {
                CategoryManager.categories.push(cat);
            });

            const hiddenInput = document.getElementById('categories-input');
            if (hiddenInput) {
                hiddenInput.value = CategoryManager.categories.map(c => c.slug).join(',');
            }

            CategoryManager.renderTags();
        });
    }

    // ============================================
    // Meta Description Counter
    // ============================================
    function initMetaCounter() {
        const input = document.querySelector('[name="meta_description"]');
        const counter = document.getElementById('meta-counter');
        if (!input || !counter) return;

        function update() {
            const len = input.value.length;
            counter.textContent = len + ' / 160';
            counter.style.color = len > 160 ? '#ef4444' : '#64748b';
        }

        input.addEventListener('input', update);
        update();
    }

    // ============================================
    // Initialize on DOMContentLoaded
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        initOTPInputs();
        initScheduledField();
        initCategorySelect();
        initMetaCounter();
        MarkdownPreview.init();
        AutoSave.init();
    });

})();
