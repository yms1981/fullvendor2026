#!/usr/bin/env python3
import json
from pathlib import Path

LANG_DIR = Path('/home/desarrollo/fullvendor2025/api/lang')

COMMON = {
    'es-ES': {
        'import': 'Importar', 'export': 'Exportar', 'btn_pdf': 'PDF', 'btn_download_template': 'Descargar plantilla',
        'import_page_title': 'Importar', 'import_select_file': 'Archivo CSV', 'import_submit': 'Importar',
        'import_sample_format': 'Descargar formato de ejemplo', 'import_no_file': 'No se seleccionó ningún archivo.',
        'import_result': 'Importación finalizada: %d cargados, %d con error.', 'back': 'Atrás',
        'pdf_company_label': 'Empresa', 'pdf_date_label': 'Fecha',
    },
    'en-EN': {
        'import': 'Import', 'export': 'Export', 'btn_pdf': 'PDF', 'btn_download_template': 'Download template',
        'import_page_title': 'Import', 'import_select_file': 'CSV file', 'import_submit': 'Import',
        'import_sample_format': 'Download sample format', 'import_no_file': 'No file selected.',
        'import_result': 'Import finished: %d loaded, %d failed.', 'back': 'Back',
        'pdf_company_label': 'Company', 'pdf_date_label': 'Date',
    },
    'de-DE': {
        'import': 'Importieren', 'export': 'Exportieren', 'btn_pdf': 'PDF', 'btn_download_template': 'Vorlage herunterladen',
        'import_page_title': 'Importieren', 'import_select_file': 'CSV-Datei', 'import_submit': 'Importieren',
        'import_sample_format': 'Beispielformat herunterladen', 'import_no_file': 'Keine Datei ausgewählt.',
        'import_result': 'Import abgeschlossen: %d geladen, %d fehlgeschlagen.', 'back': 'Zurück',
        'pdf_company_label': 'Unternehmen', 'pdf_date_label': 'Datum',
    },
    'it-IT': {
        'import': 'Importa', 'export': 'Esporta', 'btn_pdf': 'PDF', 'btn_download_template': 'Scarica modello',
        'import_page_title': 'Importa', 'import_select_file': 'File CSV', 'import_submit': 'Importa',
        'import_sample_format': 'Scarica formato di esempio', 'import_no_file': 'Nessun file selezionato.',
        'import_result': 'Importazione completata: %d caricati, %d errori.', 'back': 'Indietro',
        'pdf_company_label': 'Azienda', 'pdf_date_label': 'Data',
    },
    'pt-PT': {
        'import': 'Importar', 'export': 'Exportar', 'btn_pdf': 'PDF', 'btn_download_template': 'Descarregar modelo',
        'import_page_title': 'Importar', 'import_select_file': 'Ficheiro CSV', 'import_submit': 'Importar',
        'import_sample_format': 'Descarregar formato de exemplo', 'import_no_file': 'Nenhum ficheiro selecionado.',
        'import_result': 'Importação concluída: %d carregados, %d com erro.', 'back': 'Voltar',
        'pdf_company_label': 'Empresa', 'pdf_date_label': 'Data',
    },
    'ru-RU': {
        'import': 'Импорт', 'export': 'Экспорт', 'btn_pdf': 'PDF', 'btn_download_template': 'Скачать шаблон',
        'import_page_title': 'Импорт', 'import_select_file': 'CSV файл', 'import_submit': 'Импорт',
        'import_sample_format': 'Скачать образец', 'import_no_file': 'Файл не выбран.',
        'import_result': 'Импорт завершён: %d загружено, %d ошибок.', 'back': 'Назад',
        'pdf_company_label': 'Компания', 'pdf_date_label': 'Дата',
    },
    'zh-CN': {
        'import': '导入', 'export': '导出', 'btn_pdf': 'PDF', 'btn_download_template': '下载模板',
        'import_page_title': '导入', 'import_select_file': 'CSV 文件', 'import_submit': '导入',
        'import_sample_format': '下载示例格式', 'import_no_file': '未选择文件。',
        'import_result': '导入完成：%d 成功，%d 失败。', 'back': '返回',
        'pdf_company_label': '公司', 'pdf_date_label': '日期',
    },
}

MODULE_PATCHES = {
    'es-ES': {
        'unit-languaje': {
            'pageTitle': 'Lista de tipos de unidades', 'decimales': 'Decimales',
            'import_page_title': 'Importar unidades', 'import_columns_note': 'Columnas: unidad, descripción, formato (entero|decimal), decimales, por defecto (0|1)',
        },
        'cmp-terms': {
            'pageTitle': 'Lista de términos de venta', 'import_page_title': 'Importar términos de venta',
            'import_columns_note': 'Columnas: nombre, por defecto (0|1)',
        },
        'cmp-group': {
            'pageTitle': 'Lista de grupos de clientes', 'list_page_title': 'Lista de grupos de clientes',
            'import_page_title': 'Importar grupos de clientes',
            'import_columns_note': 'Columnas: nombre, tipo porcentaje, monto porcentaje, por defecto (0|1)',
        },
        'cmp-user': {
            'pageTitle': 'Lista de usuarios', 'import_page_title': 'Importar usuarios',
            'import_columns_note': 'Columnas: nombre, email, contraseña, rol (manager|seller|warehouse|customer), id perfil, teléfono',
        },
        'cmp-profile-vendor': {
            'pageTitle': 'Lista de perfiles de vendedores', 'no': 'No',
            'import_page_title': 'Importar perfiles de vendedores',
            'import_columns_note': 'Columnas: nombre perfil, descuento orden, % descuento, descuento neto, % descuento neto, cambiar precio, enviar catálogo, crear cliente, por defecto',
        },
        'cm-currency': {
            'pageTitle': 'Lista de monedas', 'import_page_title': 'Importar moneda de la empresa',
            'import_columns_note': 'Columna: código de moneda (ej. USD, EUR)',
            'import_currency_invalid': 'Código de moneda no válido.', 'import_currency_not_found': 'Moneda no encontrada.',
        },
    },
    'en-EN': {
        'unit-languaje': {
            'pageTitle': 'Units list', 'decimales': 'Decimals',
            'import_page_title': 'Import units', 'import_columns_note': 'Columns: unit, description, format (entero|decimal), decimals, default (0|1)',
        },
        'cmp-terms': {
            'pageTitle': 'Terms of sale list', 'import_page_title': 'Import terms of sale',
            'import_columns_note': 'Columns: name, default (0|1)',
        },
        'cmp-group': {
            'pageTitle': 'Customer groups list', 'list_page_title': 'Customer groups list',
            'import_page_title': 'Import customer groups',
            'import_columns_note': 'Columns: name, percentage type, percent amount, default (0|1)',
        },
        'cmp-user': {
            'pageTitle': 'Users list', 'import_page_title': 'Import users',
            'import_columns_note': 'Columns: name, email, password, role (manager|seller|warehouse|customer), profile id, phone',
        },
        'cmp-profile-vendor': {
            'pageTitle': 'Seller profiles list', 'no': 'No',
            'import_page_title': 'Import seller profiles',
            'import_columns_note': 'Columns: profile name, order discount, discount %, net discount, net discount %, change price, send catalog, create customer, default',
        },
        'cm-currency': {
            'pageTitle': 'Currency list', 'import_page_title': 'Import company currency',
            'import_columns_note': 'Column: currency code (e.g. USD, EUR)',
            'import_currency_invalid': 'Invalid currency code.', 'import_currency_not_found': 'Currency not found.',
        },
    },
}

# Copy EN patches to other langs with translated module keys where we only have common
for loc in ['de-DE', 'it-IT', 'pt-PT', 'ru-RU', 'zh-CN']:
    MODULE_PATCHES[loc] = {}
    base = MODULE_PATCHES['en-EN']
    for section, keys in base.items():
        MODULE_PATCHES[loc][section] = dict(keys)
        if section == 'unit-languaje':
            MODULE_PATCHES[loc][section]['import_page_title'] = COMMON[loc]['import_page_title'] + ' units' if loc != 'zh-CN' else '导入单位'
        # keep English column notes for non es/en for now - user prefers translations; use EN as fallback is ok for column notes

for locale_file in LANG_DIR.glob('*.json'):
    locale = locale_file.stem
    data = json.loads(locale_file.read_text(encoding='utf-8'))
    data['config-crud-tools'] = COMMON.get(locale, COMMON['en-EN'])
    patches = MODULE_PATCHES.get(locale, MODULE_PATCHES.get('en-EN', {}))
    for section, keys in patches.items():
        if section not in data or not isinstance(data[section], dict):
            data[section] = {}
        data[section].update(keys)
    locale_file.write_text(json.dumps(data, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print('Updated', locale_file.name)
