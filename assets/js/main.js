let editor;
let editors = {};
let currentTheme = 'monokai';
let tabCounter = 1;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize first editor
    initializeEditor('code', 'content1');

    // Theme toggle
    document.getElementById('themeToggle').addEventListener('click', function() {
        currentTheme = currentTheme === 'monokai' ? 'dracula' : 'monokai';
        document.body.classList.toggle('dark-theme');
        Object.values(editors).forEach(editor => {
            editor.setOption('theme', currentTheme);
        });
    });

    // Format code
    document.getElementById('formatCode').addEventListener('click', function() {
        const currentEditor = editors[getCurrentTabId()];
        if (currentEditor) {
            const outputDiv = document.getElementById('output');
            outputDiv.innerHTML = 'Форматирование кода...\n';
            
            $.ajax({
                url: 'format.php',
                method: 'POST',
                data: { code: currentEditor.getValue() },
                success: function(response) {
                    if (response.formatted) {
                        currentEditor.setValue(response.formatted);
                        outputDiv.innerHTML = 'Код отформатирован\n';
                    }
                }
            });
        }
    });

    // New file
    document.getElementById('newFile').addEventListener('click', function() {
        createNewTab();
    });

    // Open file
    document.getElementById('openFileBtn').addEventListener('click', function() {
        document.getElementById('openFile').click();
    });

    document.getElementById('openFile').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                createNewTab(file.name, e.target.result);
            };
            reader.readAsText(file);
        }
    });

    // Run Code button click handler
    document.getElementById('runBtn').addEventListener('click', function() {
        const currentEditor = editors[getCurrentTabId()];
        if (!currentEditor) {
            alert('Нет активного редактора');
            return;
        }
        
        const code = currentEditor.getValue();
        if (!code.trim()) {
            alert('Код не может быть пустым');
            return;
        }

        const outputDiv = document.getElementById('output');
        outputDiv.innerHTML = 'Выполнение кода...\n';

        // Формируем данные для отправки
        const formData = new FormData();
        formData.append('code', code);

        // Отправляем запрос
        fetch('execute.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text().then(text => {
                console.log('Raw response text:', text);
                console.log('Response text length:', text.length);
                if (!text) {
                    throw new Error('Empty response from server');
                }
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Parse error:', e);
                    throw new Error('Invalid JSON response: ' + text);
                }
            });
        })
        .then(data => {
            console.log('Parsed response:', data);
            if (data.success) {
                outputDiv.innerHTML = data.output || 'Нет вывода';
            } else {
                outputDiv.innerHTML = 'Ошибка:\n' + (data.error || 'Неизвестная ошибка');
            }
        })
        .catch(error => {
            console.error('Execute error:', error);
            outputDiv.innerHTML = 'Ошибка выполнения кода: ' + error.message;
        });
    });

    // Save Code button click handler
    document.getElementById('saveBtn').addEventListener('click', function() {
        const currentEditor = editors[getCurrentTabId()];
        if (!currentEditor) {
            alert('Нет активного редактора');
            return;
        }
        
        const code = currentEditor.getValue();
        if (!code.trim()) {
            alert('Код не может быть пустым');
            return;
        }

        const filename = getCurrentTabName();
        
        $.ajax({
            url: 'save.php',
            method: 'POST',
            data: { 
                code: code,
                filename: filename
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Обновляем имя текущей вкладки, если это новый файл
                    const currentTab = document.querySelector('#editorTabs .nav-link.active');
                    if (currentTab && response.filename) {
                        currentTab.textContent = response.filename + ' ×';
                    }
                    updateFileExplorer();
                }
                alert(response.message);
            },
            error: function(xhr, status, error) {
                console.error('Save error:', error);
                console.error('Response:', xhr.responseText);
                try {
                    const response = JSON.parse(xhr.responseText);
                    alert(response.message || 'Ошибка сохранения файла');
                } catch(e) {
                    console.error('Parse error:', e);
                    alert('Ошибка сохранения файла: ' + error);
                }
            }
        });
    });

    // Initialize file explorer
    updateFileExplorer();
});

function initializeEditor(textareaId, containerId) {
    const textarea = document.getElementById(textareaId);
    if (!textarea) return;

    const editor = CodeMirror.fromTextArea(textarea, {
        mode: 'python',
        theme: currentTheme,
        lineNumbers: true,
        indentUnit: 4,
        autoCloseBrackets: true,
        matchBrackets: true,
        lineWrapping: true,
        viewportMargin: Infinity,
        extraKeys: {
            "Ctrl-Space": "autocomplete",
            "Tab": function(cm) {
                if (cm.somethingSelected()) {
                    cm.indentSelection("add");
                } else {
                    cm.replaceSelection("    ", "end");
                }
            }
        }
    });

    // Принудительно обновляем размер редактора после инициализации
    setTimeout(() => {
        editor.refresh();
    }, 100);

    editors[containerId] = editor;
    return editor;
}

function createNewTab(filename = null, content = '') {
    tabCounter++;
    const tabId = `tab${tabCounter}`;
    const contentId = `content${tabCounter}`;
    const tabName = filename || `untitled${tabCounter}.py`;

    // Create new tab
    const tabHtml = `
        <li class="nav-item">
            <a class="nav-link" id="${tabId}" data-bs-toggle="tab" href="#${contentId}" role="tab">
                ${tabName}
                <span class="close-tab" data-tab="${contentId}">&times;</span>
            </a>
        </li>
    `;
    document.querySelector('#editorTabs').insertAdjacentHTML('beforeend', tabHtml);

    // Create new content
    const contentHtml = `
        <div class="tab-pane fade" id="${contentId}" role="tabpanel">
            <textarea id="code${tabCounter}">${content}</textarea>
        </div>
    `;
    document.querySelector('#editorTabContent').insertAdjacentHTML('beforeend', contentHtml);

    // Initialize new editor
    const newEditor = initializeEditor(`code${tabCounter}`, contentId);

    // Show new tab
    $(`#${tabId}`).tab('show');

    // Add close handler
    document.querySelector(`#${tabId} .close-tab`).addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        closeTab(contentId);
    });

    // Принудительно обновляем размер нового редактора
    setTimeout(() => {
        newEditor.refresh();
    }, 100);
}

function closeTab(contentId) {
    const tab = document.querySelector(`a[href="#${contentId}"]`).parentElement;
    const content = document.getElementById(contentId);
    
    // If this is the active tab, switch to another tab first
    if (content.classList.contains('active')) {
        const remainingTabs = document.querySelectorAll('#editorTabs .nav-link');
        if (remainingTabs.length > 1) {
            const otherTab = Array.from(remainingTabs).find(t => t.getAttribute('href') !== `#${contentId}`);
            $(otherTab).tab('show');
        }
    }

    // Remove the editor instance
    delete editors[contentId];
    
    // Remove the tab and content
    tab.remove();
    content.remove();
}

function getCurrentTabId() {
    const activeTab = document.querySelector('#editorTabContent .tab-pane.active');
    return activeTab ? activeTab.id : 'content1';
}

function getCurrentTabName() {
    const activeTab = document.querySelector('#editorTabs .nav-link.active');
    return activeTab ? activeTab.textContent.trim().replace('×', '') : 'main.py';
}

function updateFileExplorer() {
    $.ajax({
        url: 'list_files.php',
        method: 'GET',
        success: function(response) {
            const fileExplorer = document.getElementById('fileExplorer');
            if (response.files) {
                const fileList = response.files.map(file => `
                    <div class="file-item">
                        <div class="file-name">
                            <i class="fas fa-file-code"></i>
                            <span>${file}</span>
                        </div>
                        <div class="file-actions">
                            <button class="rename-btn" title="Переименовать">
                                <i class="fas fa-edit"></i>
                                <span>Переименовать</span>
                            </button>
                            <button class="download-btn" title="Скачать">
                                <i class="fas fa-download"></i>
                                <span>Скачать</span>
                            </button>
                            <button class="delete-btn" title="Удалить">
                                <i class="fas fa-trash"></i>
                                <span>Удалить</span>
                            </button>
                        </div>
                    </div>
                `).join('');
                fileExplorer.innerHTML = fileList || '<div class="p-3 text-muted">Нет файлов</div>';

                // Add click handlers for files
                document.querySelectorAll('.file-item .file-name').forEach(item => {
                    item.addEventListener('click', function() {
                        const filename = this.querySelector('span').textContent.trim();
                        openFile(filename);
                    });
                });

                // Add rename handlers
                document.querySelectorAll('.rename-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const fileItem = this.closest('.file-item');
                        const fileName = fileItem.querySelector('.file-name span').textContent.trim();
                        renameFile(fileItem, fileName);
                    });
                });

                // Add download handlers
                document.querySelectorAll('.download-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const fileName = this.closest('.file-item').querySelector('.file-name span').textContent.trim();
                        downloadFile(fileName);
                    });
                });

                // Add delete handlers
                document.querySelectorAll('.delete-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const fileName = this.closest('.file-item').querySelector('.file-name span').textContent.trim();
                        deleteFile(fileName);
                    });
                });
            }
        }
    });
}

function renameFile(fileItem, oldName) {
    const fileNameDiv = fileItem.querySelector('.file-name');
    const currentName = oldName.replace(/\.py$/, '');
    
    // Create rename input
    const renameHtml = `
        <div class="d-flex align-items-center w-100">
            <input type="text" class="form-control form-control-sm rename-input" value="${currentName}">
            <button class="btn btn-sm btn-success confirm-rename">OK</button>
            <button class="btn btn-sm btn-secondary cancel-rename">×</button>
        </div>
    `;
    
    const originalContent = fileNameDiv.innerHTML;
    fileNameDiv.innerHTML = renameHtml;
    
    const input = fileNameDiv.querySelector('.rename-input');
    input.focus();
    input.select();
    
    // Handle confirm rename
    fileNameDiv.querySelector('.confirm-rename').addEventListener('click', function() {
        const newName = input.value.trim();
        if (newName && newName !== currentName) {
            $.ajax({
                url: 'file_operations.php',
                method: 'POST',
                data: {
                    action: 'rename',
                    filename: oldName,
                    newFilename: newName
                },
                success: function(response) {
                    if (response.success) {
                        updateFileExplorer();
                    } else {
                        alert('Ошибка: ' + (response.error || 'Не удалось переименовать файл'));
                        fileNameDiv.innerHTML = originalContent;
                    }
                },
                error: function() {
                    alert('Ошибка при переименовании файла');
                    fileNameDiv.innerHTML = originalContent;
                }
            });
        } else {
            fileNameDiv.innerHTML = originalContent;
        }
    });
    
    // Handle cancel rename
    fileNameDiv.querySelector('.cancel-rename').addEventListener('click', function() {
        fileNameDiv.innerHTML = originalContent;
    });
    
    // Handle Enter key
    input.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            fileNameDiv.querySelector('.confirm-rename').click();
        } else if (e.key === 'Escape') {
            fileNameDiv.querySelector('.cancel-rename').click();
        }
    });
}

function downloadFile(filename) {
    $.ajax({
        url: 'file_operations.php',
        method: 'POST',
        data: {
            action: 'download',
            filename: filename
        },
        xhrFields: {
            responseType: 'blob'
        },
        success: function(response) {
            // Создаем ссылку для скачивания
            const url = window.URL.createObjectURL(new Blob([response]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            link.parentNode.removeChild(link);
            window.URL.revokeObjectURL(url);
        },
        error: function(xhr, status, error) {
            console.error('Download error:', error);
            try {
                const reader = new FileReader();
                reader.onload = function() {
                    const response = JSON.parse(this.result);
                    alert(response.error || 'Ошибка скачивания файла');
                };
                reader.readAsText(xhr.response);
            } catch(e) {
                alert('Ошибка скачивания файла');
            }
        }
    });
}

function deleteFile(filename) {
    if (confirm(`Вы уверены, что хотите удалить файл "${filename}"?`)) {
        $.ajax({
            url: 'file_operations.php',
            method: 'POST',
            data: {
                action: 'delete',
                filename: filename
            },
            success: function(response) {
                if (response.success) {
                    updateFileExplorer();
                    // Если файл открыт в редакторе, закрываем его вкладку
                    const tabs = document.querySelectorAll('#editorTabs .nav-link');
                    tabs.forEach(tab => {
                        if (tab.textContent.trim().replace('×', '') === filename) {
                            const contentId = tab.getAttribute('href').substring(1);
                            closeTab(contentId);
                        }
                    });
                } else {
                    alert('Ошибка: ' + (response.error || 'Не удалось удалить файл'));
                }
            },
            error: function() {
                alert('Ошибка при удалении файла');
            }
        });
    }
}

function openFile(filename) {
    $.ajax({
        url: 'open_file.php',
        method: 'GET',
        data: { filename: filename },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.content !== undefined) {
                createNewTab(response.filename, response.content);
            } else {
                alert('Ошибка: ' + (response.error || 'Не удалось открыть файл'));
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            console.error('Response:', xhr.responseText);
            alert('Ошибка открытия файла: ' + error);
        }
    });
}
