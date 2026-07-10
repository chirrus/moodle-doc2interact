<?php
$string['modulename']           = 'Doc2Interact';
$string['modulenameplural']     = 'Doc2Interact';
$string['modulename_help']      = 'Convierte documentos en contenido educativo interactivo directamente en tu curso.';
$string['pluginname']           = 'Doc2Interact';
$string['pluginadministration'] = 'Administración Doc2Interact';

// Configuración admin
$string['apikey']               = 'API Key';
$string['apikey_desc']          = 'Ingresá tu API Key de Doc2Interact. Si no tenés una, se usará la clave de prueba con limitaciones.';
$string['apiurl']               = 'URL de la API';
$string['apiurl_desc']          = 'URL del servidor Doc2Interact. No modificar salvo indicación expresa.';

// Panel docente
$string['uploadfile']           = 'Subir documento';
$string['uploadfile_help']      = 'Subí un PDF o DOCX. Doc2Interact generará los recursos automáticamente en este curso.';
$string['extrainstructions']    = 'Instrucciones adicionales';
$string['extrainstructions_help'] = 'Opcional. Podés indicar colores, fuente, logo u otras preferencias.';
$string['contentype']           = 'Tipo de contenido';
$string['pagina']               = 'Página web interactiva';
$string['slides']               = 'Presentación de diapositivas';
$string['generate']             = 'Generar contenido';
$string['generating']           = 'Generando contenido, por favor esperá...';

// Resultados
$string['result_html']          = 'Contenido interactivo';
$string['result_quiz']          = 'Autoevaluación';
$string['result_h5p']           = 'Actividad H5P';
$string['result_gift']          = 'Banco de preguntas';
$string['created_ok']           = 'Recursos creados exitosamente en el curso.';
$string['created_error']        = 'Hubo un error al crear algunos recursos. Revisá el log.';

// Errores
$string['error_nofile']         = 'Debés subir un archivo PDF o DOCX.';
$string['error_api']            = 'Error al conectar con la API de Doc2Interact.';
$string['error_credits']        = 'Créditos agotados. Contactá a Doc2Interact para recargar.';

// Mensajes de interfaz
$string['teacheronly']          = 'Esta actividad es solo para docentes.';
$string['notext']               = 'Debés subir un archivo y esperar que se extraiga el texto.';

// Privacidad
$string['privacy:metadata:doc2interact_api']                  = 'Doc2Interact envía el texto del documento a su servidor API para generar contenido.';
$string['privacy:metadata:doc2interact_api:textoCompleto']    = 'El texto extraído del documento subido.';
$string['privacy:metadata:doc2interact_api:titulo']           = 'El título del contenido generado.';
