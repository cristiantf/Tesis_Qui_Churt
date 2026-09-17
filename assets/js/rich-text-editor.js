(function () {
    'use strict';

    function command(editor, name, value) {
        editor.focus();
        document.execCommand(name, false, value || null);
        sync(editor);
    }

    function sync(editor) {
        editor._textarea.value = editor.innerHTML.trim();
    }

    function createButton(label, title, action) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'rte-button';
        button.innerHTML = label;
        button.title = title;
        button.addEventListener('click', action);
        return button;
    }

    function init(textarea) {
        if (textarea.dataset.rteReady) return;
        textarea.dataset.rteReady = 'true';
        textarea.classList.add('rte-source');

        var wrapper = document.createElement('div');
        wrapper.className = 'rich-text-editor';
        var toolbar = document.createElement('div');
        toolbar.className = 'rte-toolbar';
        var editor = document.createElement('div');
        editor.className = 'rte-content form-control';
        editor.contentEditable = 'true';
        editor.setAttribute('role', 'textbox');
        editor.setAttribute('aria-multiline', 'true');
        editor.innerHTML = textarea.value;
        editor._textarea = textarea;

        toolbar.append(
            createButton('<b>B</b>', 'Negrita', function () { command(editor, 'bold'); }),
            createButton('<i>I</i>', 'Cursiva', function () { command(editor, 'italic'); }),
            createButton('<u>U</u>', 'Subrayado', function () { command(editor, 'underline'); }),
            createButton('<i class="bi bi-list-ul"></i>', 'Lista', function () { command(editor, 'insertUnorderedList'); }),
            createButton('<i class="bi bi-list-ol"></i>', 'Lista numerada', function () { command(editor, 'insertOrderedList'); }),
            createButton('H', 'Título', function () { command(editor, 'formatBlock', 'h4'); }),
            createButton('<i class="bi bi-link-45deg"></i>', 'Enlace', function () {
                var url = window.prompt('Dirección del enlace (https://...)');
                if (url) command(editor, 'createLink', url);
            }),
            createButton('<i class="bi bi-eraser"></i>', 'Quitar formato', function () { command(editor, 'removeFormat'); })
        );

        var color = document.createElement('input');
        color.type = 'color';
        color.className = 'rte-color';
        color.title = 'Color de texto';
        color.addEventListener('input', function () { command(editor, 'foreColor', color.value); });
        toolbar.appendChild(color);

        editor.addEventListener('input', function () { sync(editor); });
        wrapper.append(toolbar, editor);
        textarea.parentNode.insertBefore(wrapper, textarea.nextSibling);
        textarea.form && textarea.form.addEventListener('submit', function () { sync(editor); });
    }

    window.RichTextEditor = {
        setValue: function (selector, value) {
            var textarea = typeof selector === 'string' ? document.querySelector(selector) : selector;
            if (!textarea) return;
            textarea.value = value || '';
            var editor = textarea.parentNode.querySelector('.rich-text-editor .rte-content');
            if (editor) editor.innerHTML = textarea.value;
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('textarea[data-rich-text]').forEach(init);
    });
}());
