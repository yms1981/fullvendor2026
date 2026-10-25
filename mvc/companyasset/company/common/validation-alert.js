/**
 * Alerta genérica de validación
 * Recibe un texto dinámico a mostrar
 * Si no hay texto específico, muestra un mensaje genérico descriptivo
 */
(function() {
    'use strict';
    
    // Función para obtener las traducciones
    function getValidationTranslations() {
        const validationLang = window.validationLang || {};
        const uiLang = window.fvUiLang || {};
        const merged = Object.assign({}, validationLang, uiLang);

        const pick = function (key) {
            const value = merged[key];
            return (typeof value === 'string' && value.trim() !== '') ? value : '';
        };

        return {
            unit_duplicate: pick('unit_duplicate'),
            group_duplicate: pick('group_duplicate') || pick('group_existente'),
            category_duplicate: pick('category_duplicate') || pick('category_exists'),
            generic_error: pick('generic_error') || pick('message_error_generic'),
            ok: pick('ok'),
            close: pick('close')
        };
    }
    
    function isCategoryDuplicateError(message) {
        if (!message) return false;
        const msg = String(message).toLowerCase();
        return (
            msg.includes('category name') ||
            msg.includes('nombre de categoria') ||
            msg.includes('nombre de categoría') ||
            msg.includes('categoria') ||
            msg.includes('category')
        ) && (
            msg.includes('already exists') ||
            msg.includes('ya existe') ||
            msg.includes('ya ha sido tomado') ||
            msg.includes('has been taken') ||
            msg.includes('ha sido tomado')
        );
    }

    function isGroupDuplicateError(message) {
        if (!message) return false;
        const msg = String(message).toLowerCase();
        return (
            msg.includes('group name') ||
            msg.includes('nombre del grupo') ||
            msg.includes('grupo de cliente') ||
            msg.includes('customer group') ||
            msg.includes('gruppo') ||
            msg.includes('gruppe') ||
            msg.includes('grupo')
        ) && (
            msg.includes('already exists') ||
            msg.includes('ya existe') ||
            msg.includes('already been') ||
            msg.includes('ha sido tomado') ||
            msg.includes('existe')
        );
    }
    
    /**
     * Función para detectar si es un error de unidad duplicada
     * @param {String} message - Mensaje a verificar
     * @returns {Boolean}
     */
    function isUnitDuplicateError(message) {
        if (!message) return false;
        const msg = message.toLowerCase();
        // Detectar si es error de unidad duplicada (más flexible)
        // Verificar si contiene "unit" o "unidad"
        const hasUnit = msg.includes('unit') || msg.includes('unidad');
        
        if (!hasUnit) return false;
        
        // Verificar patrones de duplicado/tomado
        return (
            msg.includes('taken') || 
            msg.includes('ya existe') || 
            msg.includes('already been') || 
            msg.includes('ha sido tomada') || 
            msg.includes('ha sido tomado') ||
            msg.includes('sido tomado') ||
            msg.includes('sido tomada') ||
            msg.includes('termino de venta') ||
            msg.includes('already exists') ||
            msg.includes('ya ha sido') ||
            msg.includes('ha sido') ||
            msg.includes('tomado') ||
            msg.includes('tomada') ||
            msg.includes('duplicado') ||
            msg.includes('duplicada')
        );
    }
    
    /**
     * Alerta genérica de validación
     * Recibe un objeto de error con message y errors, o un string con el mensaje
     * Si no hay mensaje específico, muestra un mensaje genérico descriptivo
     * 
     * @param {Object|String} errorResponse - Objeto con {message, errors} o string con mensaje (obligatorio)
     * @param {String} customDescription - Descripción adicional opcional. Si está vacío, no se muestra descripción
     */
    window.alert_validation = function(errorResponse, customDescription) {
        const translations = getValidationTranslations();
        let title = '';
        let errorText = '';
        const genericMessage = translations.generic_error;
        const unitDuplicateMessage = translations.unit_duplicate;
        const groupDuplicateMessage = translations.group_duplicate;
        const categoryDuplicateMessage = translations.category_duplicate;
        
        // Si se proporciona una descripción personalizada y no está vacía, usarla
        const hasCustomDescription = customDescription && 
                                     typeof customDescription === 'string' && 
                                     customDescription.trim() !== '';
        
        // Si es un string, verificar si es error de unidad duplicada
        if (typeof errorResponse === 'string') {
            if (isUnitDuplicateError(errorResponse)) {
                title = unitDuplicateMessage;
            } else if (isCategoryDuplicateError(errorResponse)) {
                title = (hasCustomDescription ? customDescription : '') || categoryDuplicateMessage || errorResponse;
            } else if (isGroupDuplicateError(errorResponse)) {
                title = groupDuplicateMessage || (hasCustomDescription ? customDescription : errorResponse);
            } else {
                title = errorResponse || genericMessage;
            }
        }
        // Si es un objeto con responseJSON (error de AJAX)
        else if (errorResponse && errorResponse.responseJSON) {
            const response = errorResponse.responseJSON;
            let isUnitError = false;
            
            // PRIMERO: Verificar si el mensaje principal es de unidad duplicada
            if (response.message || response.msg) {
                const msg = response.message || response.msg;
                const msgStr = typeof msg === 'string' ? msg : String(msg);
                if (isUnitDuplicateError(msgStr)) {
                    isUnitError = true;
                    title = unitDuplicateMessage;
                } else if (isCategoryDuplicateError(msgStr)) {
                    title = (hasCustomDescription ? customDescription : '') || categoryDuplicateMessage || msgStr;
                } else if (isGroupDuplicateError(msgStr)) {
                    title = (hasCustomDescription ? customDescription : '') || groupDuplicateMessage || msgStr;
                }
            }
            
            // También verificar en responseText si existe (por si el mensaje viene ahí)
            if (!isUnitError && errorResponse.responseText) {
                try {
                    const responseText = typeof errorResponse.responseText === 'string' ? errorResponse.responseText : String(errorResponse.responseText);
                    if (isUnitDuplicateError(responseText)) {
                        isUnitError = true;
                        title = unitDuplicateMessage;
                    }
                } catch (e) {
                    // Ignorar errores al parsear
                }
            }
            
            // PRIMERO: Verificar si hay errores de validación descriptivos (tienen prioridad sobre mensaje genérico)
            let hasDescriptiveErrors = false;
            if (response.errors) {
                const errors = response.errors;
                const errorMessages = Object.values(errors)
                    .reduce((acc, curr) => {
                        if (Array.isArray(curr)) {
                            return acc.concat(curr);
                        }
                        return acc.concat([curr]);
                    }, [])
                    .filter(msg => msg && msg.trim() !== '')
                    .map(msg => msg.trim());
                
                // Si hay errores de validación descriptivos, usarlos en lugar del mensaje genérico
                if (errorMessages.length > 0) {
                    // Verificar si el primer error es más descriptivo que un mensaje genérico
                    const firstError = errorMessages[0];
                    // Si el error no es genérico (no contiene "Error al crear" o similar), es descriptivo
                    if (firstError && 
                        !firstError.toLowerCase().includes('error al crear') &&
                        !firstError.toLowerCase().includes('error al actualizar') &&
                        !firstError.toLowerCase().includes('error al guardar') &&
                        !firstError.toLowerCase().includes('an error has occurred')) {
                        hasDescriptiveErrors = true;
                    }
                }
            }
            
            // PRIMERO: Verificar si hay un mensaje principal claro (prioridad)
            // Solo si NO hay errores descriptivos de validación
            let hasMainMessage = false;
            // Si hay errores descriptivos, NO procesar el mensaje principal para evitar duplicación
            if (!isUnitError && !hasDescriptiveErrors && (response.message || response.msg)) {
                const msg = response.message || response.msg;
                const msgStr = typeof msg === 'string' ? msg : String(msg);
                // Si el mensaje no es genérico y es descriptivo, usarlo como título
                // PERO solo si no hay errores descriptivos que ya establecieron el título
                if (msgStr && msgStr.trim() !== '' && msgStr !== genericMessage && !isUnitDuplicateError(msgStr) && !title) {
                    title = msgStr;
                    hasMainMessage = true;
                    errorText = ''; // Asegurar que errorText esté vacío desde el inicio
                }
            }
            
            // Procesar errores de validación (solo si no hay un título ya establecido)
            if (response.errors) {
                const errors = response.errors;
                
                // Si hay errores descriptivos de validación, tienen prioridad sobre mensaje genérico
                if (hasDescriptiveErrors && !isUnitError) {
                    // Procesar errores descriptivos primero
                    const errorMessages = Object.values(errors)
                        .reduce((acc, curr) => {
                            if (Array.isArray(curr)) {
                                return acc.concat(curr);
                            }
                            return acc.concat([curr]);
                        }, [])
                        .filter(msg => msg && msg.trim() !== '')
                        .map(msg => msg.trim());
                    
                    if (errorMessages.length > 0) {
                        if (isUnitDuplicateError(errorMessages[0])) {
                            title = unitDuplicateMessage;
                        } else if (isCategoryDuplicateError(errorMessages[0])) {
                            title = (hasCustomDescription ? customDescription : '') || categoryDuplicateMessage || errorMessages[0];
                        } else if (isGroupDuplicateError(errorMessages[0])) {
                            title = (hasCustomDescription ? customDescription : '') || groupDuplicateMessage || errorMessages[0];
                        } else {
                            // Usar solo el primer error como título - no concatenar con mensaje principal
                            title = errorMessages[0];
                        }
                        // Asegurar que errorText esté vacío - solo mostrar título
                        errorText = '';
                    }
                    // IMPORTANTE: No procesar más, ya tenemos el título desde los errores descriptivos
                    // Marcar que ya se procesó para evitar que se procese el mensaje principal después
                    hasMainMessage = false; // Evitar que se procese el mensaje principal
                } else if (hasMainMessage || (title && !isUnitError)) {
                    // Si ya hay un título establecido desde el mensaje principal, NO procesar errores
                    // para evitar duplicación
                    errorText = ''; // Asegurar que errorText esté vacío
                } else {
                    // Verificar si hay error en el campo 'unit' (solo si aún no se detectó como error de unidad)
                    if (errors.unit && !isUnitError) {
                        const unitErrors = Array.isArray(errors.unit) ? errors.unit : [errors.unit];
                        if (unitErrors.some(err => isUnitDuplicateError(err))) {
                            isUnitError = true;
                            title = unitDuplicateMessage;
                            // Comentado: texto adicional deshabilitado por ahora
                            // Si hay más errores además del de unit, agregarlos como texto
                            // const otherErrors = Object.keys(errors)
                            //     .filter(key => key !== 'unit')
                            //     .reduce((acc, key) => {
                            //         const errs = Array.isArray(errors[key]) ? errors[key] : [errors[key]];
                            //         return acc.concat(errs);
                            //     }, []);
                            // if (otherErrors.length > 0) {
                            //     errorText = otherErrors.join('\n');
                            // }
                        }
                    }
                    
                    // Verificar si hay error en el campo 'name' que pueda ser término duplicado
                    // Solo si no es error de unidad y hay un mensaje personalizado
                    if (errors.name && !isUnitError && hasCustomDescription) {
                        const nameErrors = Array.isArray(errors.name) ? errors.name : [errors.name];
                        const nameErrorMsg = nameErrors.join(' ').toLowerCase();
                        // Verificar si es un error de término duplicado
                        if (nameErrorMsg.includes('ha sido tomado') || 
                            nameErrorMsg.includes('has been taken') || 
                            nameErrorMsg.includes('ya ha sido tomado') ||
                            nameErrorMsg.includes('already been taken')) {
                            // Usar el mensaje personalizado pasado como parámetro
                            title = customDescription;
                            // Comentado: texto adicional deshabilitado por ahora
                            // Si hay más errores además del de name, agregarlos como texto
                            // const otherErrors = Object.keys(errors)
                            //     .filter(key => key !== 'name')
                            //     .reduce((acc, key) => {
                            //         const errs = Array.isArray(errors[key]) ? errors[key] : [errors[key]];
                            //         return acc.concat(errs);
                            //     }, []);
                            // if (otherErrors.length > 0) {
                            //     errorText = otherErrors.join('\n');
                            // }
                        }
                    }
                    
                    // Si no hay título establecido, procesar errores normalmente
                    if (!isUnitError && !title) {
                        const errorMessages = Object.values(errors)
                            .reduce((acc, curr) => {
                                if (Array.isArray(curr)) {
                                    return acc.concat(curr);
                                }
                                return acc.concat([curr]);
                            }, [])
                            .filter(msg => msg && msg.trim() !== '')
                            .map(msg => msg.trim());
                        
                        if (errorMessages.length > 0) {
                            // Verificar si el primer error es de unidad duplicada
                            if (isUnitDuplicateError(errorMessages[0])) {
                                title = unitDuplicateMessage;
                                // Comentado: texto adicional deshabilitado por ahora
                                // if (errorMessages.length > 1) {
                                //     errorText = errorMessages.slice(1).join('\n');
                                // }
                            } else {
                                title = errorMessages[0];
                                // Comentado: texto adicional deshabilitado por ahora
                                // if (errorMessages.length > 1) {
                                //     errorText = errorMessages.slice(1).join('\n');
                                // }
                            }
                        }
                    }
                }
            }
            
            // Si aún no hay título pero hay un mensaje, usarlo (solo si no es de unidad)
            // NO procesar si ya hay errores descriptivos establecidos o si ya hay un título
            if (!title && !hasDescriptiveErrors && (response.message || response.msg)) {
                const msg = response.message || response.msg;
                // Si no es de unidad duplicada, usar el mensaje tal cual
                if (!isUnitDuplicateError(msg)) {
                    title = msg;
                    // Asegurar que errorText esté vacío - solo mostrar título
                    errorText = '';
                }
            }
            
            // Si aún no hay título, usar el genérico
            if (!title) {
                title = genericMessage;
            }
            
            // Asegurar que errorText esté siempre vacío cuando hay un título - solo mostrar título
            errorText = '';
        }
        // Si es un objeto directo con message y errors
        else if (errorResponse && typeof errorResponse === 'object') {
            let isUnitError = false;
            
            // PRIMERO: Verificar si el mensaje principal es de unidad duplicada
            if (errorResponse.message || errorResponse.msg) {
                const msg = errorResponse.message || errorResponse.msg;
                // Convertir a string si es necesario
                const msgStr = typeof msg === 'string' ? msg : String(msg);
                if (isUnitDuplicateError(msgStr)) {
                    isUnitError = true;
                    title = unitDuplicateMessage;
                }
            }
            
            // PRIMERO: Verificar si hay errores de validación descriptivos (tienen prioridad sobre mensaje genérico)
            let hasDescriptiveErrors = false;
            if (errorResponse.errors) {
                const errors = errorResponse.errors;
                const errorMessages = Object.values(errors)
                    .reduce((acc, curr) => {
                        if (Array.isArray(curr)) {
                            return acc.concat(curr);
                        }
                        return acc.concat([curr]);
                    }, [])
                    .filter(msg => msg && msg.trim() !== '')
                    .map(msg => msg.trim());
                
                // Si hay errores de validación descriptivos, usarlos en lugar del mensaje genérico
                if (errorMessages.length > 0) {
                    // Verificar si el primer error es más descriptivo que un mensaje genérico
                    const firstError = errorMessages[0];
                    // Si el error no es genérico (no contiene "Error al crear" o similar), es descriptivo
                    if (firstError && 
                        !firstError.toLowerCase().includes('error al crear') &&
                        !firstError.toLowerCase().includes('error al actualizar') &&
                        !firstError.toLowerCase().includes('error al guardar') &&
                        !firstError.toLowerCase().includes('an error has occurred')) {
                        hasDescriptiveErrors = true;
                    }
                }
            }
            
            // PRIMERO: Verificar si hay un mensaje principal claro (prioridad)
            // Solo si NO hay errores descriptivos de validación
            let hasMainMessage = false;
            // Si hay errores descriptivos, NO procesar el mensaje principal para evitar duplicación
            if (!isUnitError && !hasDescriptiveErrors && (errorResponse.message || errorResponse.msg)) {
                const msg = errorResponse.message || errorResponse.msg;
                const msgStr = typeof msg === 'string' ? msg : String(msg);
                // Si el mensaje no es genérico y es descriptivo, usarlo como título
                // PERO solo si no hay errores descriptivos que ya establecieron el título
                if (msgStr && msgStr.trim() !== '' && msgStr !== genericMessage && !isUnitDuplicateError(msgStr) && !title) {
                    title = msgStr;
                    hasMainMessage = true;
                    errorText = ''; // Asegurar que errorText esté vacío desde el inicio
                }
            }
            
            // Procesar errores de validación (solo si no hay un título ya establecido)
            if (errorResponse.errors) {
                const errors = errorResponse.errors;
                
                // Si hay errores descriptivos de validación, tienen prioridad sobre mensaje genérico
                if (hasDescriptiveErrors && !isUnitError) {
                    // Procesar errores descriptivos primero
                    const errorMessages = Object.values(errors)
                        .reduce((acc, curr) => {
                            if (Array.isArray(curr)) {
                                return acc.concat(curr);
                            }
                            return acc.concat([curr]);
                        }, [])
                        .filter(msg => msg && msg.trim() !== '')
                        .map(msg => msg.trim());
                    
                    if (errorMessages.length > 0) {
                        if (isUnitDuplicateError(errorMessages[0])) {
                            title = unitDuplicateMessage;
                        } else if (isCategoryDuplicateError(errorMessages[0])) {
                            title = (hasCustomDescription ? customDescription : '') || categoryDuplicateMessage || errorMessages[0];
                        } else if (isGroupDuplicateError(errorMessages[0])) {
                            title = (hasCustomDescription ? customDescription : '') || groupDuplicateMessage || errorMessages[0];
                        } else {
                            // Usar solo el primer error como título - asegurar que no contenga el mensaje genérico
                            let firstError = errorMessages[0];
                            // Si el error contiene el mensaje genérico del controlador, extraer solo la parte descriptiva
                            if (errorResponse.message || errorResponse.msg) {
                                const genericMsg = (errorResponse.message || errorResponse.msg).toLowerCase();
                                // Si el error contiene el mensaje genérico, usar solo el error (ya es más descriptivo)
                                // No hacer nada, el error ya es descriptivo
                            }
                            title = firstError;
                        }
                        // Asegurar que errorText esté vacío - solo mostrar título
                        errorText = '';
                    }
                    // IMPORTANTE: No procesar más, ya tenemos el título desde los errores descriptivos
                    // Marcar que ya se procesó para evitar que se procese el mensaje principal después
                    hasMainMessage = false; // Evitar que se procese el mensaje principal
                } else if (hasMainMessage || (title && !isUnitError)) {
                    // Si ya hay un título establecido desde el mensaje principal, NO procesar errores
                    // para evitar duplicación
                    errorText = ''; // Asegurar que errorText esté vacío
                } else {
                    // Verificar si hay error en el campo 'unit' (solo si aún no se detectó como error de unidad)
                    if (errors.unit && !isUnitError) {
                        const unitErrors = Array.isArray(errors.unit) ? errors.unit : [errors.unit];
                        if (unitErrors.some(err => isUnitDuplicateError(err))) {
                            isUnitError = true;
                            title = unitDuplicateMessage;
                            // Comentado: texto adicional deshabilitado por ahora
                            // Si hay más errores además del de unit, agregarlos como texto
                            // const otherErrors = Object.keys(errors)
                            //     .filter(key => key !== 'unit')
                            //     .reduce((acc, key) => {
                            //         const errs = Array.isArray(errors[key]) ? errors[key] : [errors[key]];
                            //         return acc.concat(errs);
                            //     }, []);
                            // if (otherErrors.length > 0) {
                            //     errorText = otherErrors.join('\n');
                            // }
                        }
                    }
                    
                    // Verificar si hay error en el campo 'name' que pueda ser término duplicado
                    // Solo si no es error de unidad y hay un mensaje personalizado
                    if (errors.name && !isUnitError && hasCustomDescription) {
                        const nameErrors = Array.isArray(errors.name) ? errors.name : [errors.name];
                        const nameErrorMsg = nameErrors.join(' ').toLowerCase();
                        // Verificar si es un error de término duplicado
                        if (nameErrorMsg.includes('ha sido tomado') || 
                            nameErrorMsg.includes('has been taken') || 
                            nameErrorMsg.includes('ya ha sido tomado') ||
                            nameErrorMsg.includes('already been taken')) {
                            // Usar el mensaje personalizado pasado como parámetro
                            title = customDescription;
                            // Comentado: texto adicional deshabilitado por ahora
                            // Si hay más errores además del de name, agregarlos como texto
                            // const otherErrors = Object.keys(errors)
                            //     .filter(key => key !== 'name')
                            //     .reduce((acc, key) => {
                            //         const errs = Array.isArray(errors[key]) ? errors[key] : [errors[key]];
                            //         return acc.concat(errs);
                            //     }, []);
                            // if (otherErrors.length > 0) {
                            //     errorText = otherErrors.join('\n');
                            // }
                        }
                    }
                    
                    // Si no hay título establecido, procesar errores normalmente
                    if (!isUnitError && !title) {
                        const errorMessages = Object.values(errors)
                            .reduce((acc, curr) => {
                                if (Array.isArray(curr)) {
                                    return acc.concat(curr);
                                }
                                return acc.concat([curr]);
                            }, [])
                            .filter(msg => msg && msg.trim() !== '')
                            .map(msg => msg.trim());
                        
                        if (errorMessages.length > 0) {
                            // Verificar si el primer error es de unidad duplicada
                            if (isUnitDuplicateError(errorMessages[0])) {
                                title = unitDuplicateMessage;
                                // Comentado: texto adicional deshabilitado por ahora
                                // if (errorMessages.length > 1) {
                                //     errorText = errorMessages.slice(1).join('\n');
                                // }
                            } else {
                                title = errorMessages[0];
                                // Comentado: texto adicional deshabilitado por ahora
                                // if (errorMessages.length > 1) {
                                //     errorText = errorMessages.slice(1).join('\n');
                                // }
                            }
                        }
                    }
                }
            }
            
            // Si aún no hay título pero hay un mensaje, usarlo (solo si no es de unidad)
            // NO procesar si ya hay errores descriptivos establecidos o si ya hay un título
            if (!title && !hasDescriptiveErrors && (errorResponse.message || errorResponse.msg)) {
                const msg = errorResponse.message || errorResponse.msg;
                // Si no es de unidad duplicada, usar el mensaje tal cual
                if (!isUnitDuplicateError(msg)) {
                    title = msg;
                    // Asegurar que errorText esté vacío - solo mostrar título
                    errorText = '';
                }
            }
            
            // Si aún no hay título, usar el genérico
            if (!title) {
                title = genericMessage;
            }
            
            // Asegurar que errorText esté siempre vacío cuando hay un título - solo mostrar título
            errorText = '';
        }
        // Si no hay nada, usar mensaje genérico
        else {
            title = genericMessage;
            errorText = '';
        }
        
        // Asegurar que errorText esté siempre vacío - solo mostrar título por ahora
        // Esto previene cualquier duplicación
        errorText = '';
        
        // Comentado: texto adicional deshabilitado por ahora
        // Si se proporcionó una descripción personalizada, usarla como errorText
        // Solo si no hay errorText ya establecido y la descripción es diferente al título
        // if (hasCustomDescription && !errorText) {
        //     if (customDescription.trim() !== title.trim()) {
        //         errorText = customDescription;
        //     }
        // }
        
        // Comentado: verificación de duplicación deshabilitada - errorText siempre está vacío
        // ANTES de mostrar la alerta: Asegurar que errorText esté vacío si el título ya contiene el mensaje
        // Esto previene duplicación cuando el mensaje aparece tanto en title como en errorText
        // if (title && errorText) {
        //     // Si errorText contiene el mismo mensaje que title, limpiarlo
        //     const titleLower = title.toLowerCase().trim();
        //     const errorTextLower = errorText.toLowerCase().trim();
        //     // Comparar si son iguales o si uno contiene al otro
        //     if (titleLower === errorTextLower || 
        //         errorTextLower.includes(titleLower) || 
        //         titleLower.includes(errorTextLower) ||
        //         // También verificar si errorText es solo una repetición del título
        //         errorTextLower.split('\n').some(line => {
        //             const lineLower = line.trim().toLowerCase();
        //             return lineLower === titleLower || lineLower.includes(titleLower) || titleLower.includes(lineLower);
        //         })) {
        //         errorText = '';
        //     }
        // }
        
        // Comentado: texto adicional deshabilitado por ahora
        // Asegurar que errorText esté vacío si el segundo parámetro está vacío o no se proporcionó
        // Si no hay descripción personalizada, no mostrar descripción
        // if (!hasCustomDescription) {
        //     errorText = '';
        // }
        
        // Comentado: texto adicional deshabilitado por ahora
        // Limpiar errorText si es igual o similar al título para evitar duplicación
        // if (errorText && errorText.trim() === title.trim()) {
        //     errorText = '';
        // }
        
        // Asegurar que errorText esté siempre vacío - solo mostrar título
        errorText = '';
        
        // Verificación final: asegurar que el título no contenga duplicaciones
        // Si el título tiene múltiples líneas, tomar solo la primera línea
        if (title && typeof title === 'string') {
            // Eliminar saltos de línea y espacios extra
            title = title.split('\n')[0].trim();
            // Si el título parece tener el mismo texto repetido, tomar solo una vez
            const titleWords = title.split(/\s+/);
            if (titleWords.length > 1) {
                const firstHalf = titleWords.slice(0, Math.ceil(titleWords.length / 2)).join(' ');
                const secondHalf = titleWords.slice(Math.ceil(titleWords.length / 2)).join(' ');
                if (firstHalf.toLowerCase() === secondHalf.toLowerCase()) {
                    title = firstHalf;
                }
            }
            // Verificar si el título contiene frases comunes de error genérico que deben eliminarse
            const genericPatterns = [
                /error al crear el término de venta\.?\s*/gi,
                /error al actualizar el término de venta\.?\s*/gi,
                /error al guardar el término de venta\.?\s*/gi,
                /error al crear la unidad de medida\.?\s*/gi,
                /error al actualizar la unidad\.?\s*/gi
            ];
            genericPatterns.forEach(pattern => {
                title = title.replace(pattern, '').trim();
            });
        }
        
        // Mostrar la alerta usando alert_func si está disponible
        if (typeof alert_func !== 'undefined') {
            // Solo mostrar el título - errorText siempre está vacío
            const fullMessage = title;
            alert_func([fullMessage, 'error']);
        } else if (typeof swal !== 'undefined') {
            // Solo mostrar el título - text siempre está vacío
            swal({
                title: title,
                text: '', // Siempre vacío - solo mostrar título
                icon: 'error',
                button: {
                    text: translations.ok,
                    value: true,
                    visible: true,
                    className: "btn btn-primary"
                }
            });
        } else {
            // Solo mostrar el título
            alert(title);
        }
    };
})();

