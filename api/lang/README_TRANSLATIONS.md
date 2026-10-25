# Sistema de Traducciones

## ⚠️ IMPORTANTE: Solo editar archivos JSON

**A partir de ahora, todas las traducciones se deben agregar ÚNICAMENTE en los archivos JSON:**

- `api/lang/en-EN.json` - Traducciones en inglés
- `api/lang/es-ES.json` - Traducciones en español

## Estructura de los archivos JSON

Los archivos JSON tienen la siguiente estructura:

```json
{
  "nombre-modulo": {
    "clave_traduccion": "Texto traducido",
    "otra_clave": "Otro texto"
  },
  "otro-modulo": {
    "clave": "Valor"
  }
}
```

Cada módulo corresponde al nombre del archivo PHP original (sin la extensión `.php`).

## Cómo agregar nuevas traducciones

### 1. Identificar el módulo

El módulo corresponde al nombre usado en `getModuleLang()`. Por ejemplo:
- `cmp-products` para productos
- `unit-languaje` para unidades
- `cmp-modal` para modales

### 2. Agregar la traducción en ambos JSON

**En `en-EN.json`:**
```json
{
  "cmp-products": {
    "nueva_clave": "New translation text"
  }
}
```

**En `es-ES.json`:**
```json
{
  "cmp-products": {
    "nueva_clave": "Nuevo texto traducido"
  }
}
```

### 3. Usar la traducción en el código

```php
// En controladores
$this->langArr = $this->getModuleLang('cmp-products');
$mensaje = $this->langArr['nueva_clave'] ?? 'Fallback text';

// En vistas (cuando $pageTexts está disponible)
<?= $pageTexts['nueva_clave'] ?? 'Fallback text' ?>
```

## Sistema de prioridad

El sistema funciona de la siguiente manera:

1. **Primero**: Intenta leer del JSON (`en-EN.json` o `es-ES.json`)
2. **Fallback**: Si el JSON no existe, lee del archivo PHP correspondiente
3. **Si no existe**: Retorna array vacío o el valor por defecto especificado con `??`

## Notas importantes

- ✅ **SÍ**: Editar directamente los archivos JSON
- ✅ **SÍ**: Agregar nuevas claves en los JSON
- ✅ **SÍ**: Modificar valores existentes en los JSON
- ❌ **NO**: Editar los archivos PHP en `/api/lang/en/` o `/api/lang/es/`
- ❌ **NO**: Regenerar los JSON desde los PHP (a menos que sea necesario para migración inicial)

## Cache de idioma

El sistema cachea el `language_id` de la compañía por 1 hora. Si se actualiza el `language_id` de una compañía, el cache se invalida automáticamente.

## Ejemplo completo

### Agregar traducción para "Guardar cambios"

1. **Agregar en `en-EN.json`:**
```json
{
  "cmp-products": {
    "save_changes": "Save changes"
  }
}
```

2. **Agregar en `es-ES.json`:**
```json
{
  "cmp-products": {
    "save_changes": "Guardar cambios"
  }
}
```

3. **Usar en el código:**
```php
$mensaje = $this->langArr['save_changes'] ?? 'Save changes';
```

