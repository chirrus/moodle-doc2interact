<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

$string['modulename']           = 'Doc2Interact';
$string['modulenameplural']     = 'Doc2Interact';
$string['modulename_help']      = 'Convierte documentos en contenido educativo interactivo directamente en tu curso.';
$string['pluginname']           = 'Doc2Interact';
$string['pluginadministration'] = 'Administración Doc2Interact';

// Admin settings
$string['apikey']               = 'API Key';
$string['apikey_desc']          = 'Ingresá tu API Key de Doc2Interact. Si no tenés una, se usará la clave de prueba con limitaciones.';
$string['apiurl']               = 'URL de la API';
$string['apiurl_desc']          = 'URL del servidor Doc2Interact. No modificar salvo indicación expresa.';

// Teacher panel
$string['uploadfile']           = 'Subir documento';
$string['uploadfile_help']      = 'Subí un PDF o DOCX. Doc2Interact generará los recursos automáticamente en este curso.';
$string['extrainstructions']    = 'Instrucciones adicionales';
$string['extrainstructions_help'] = 'Opcional. Podés indicar colores, fuente, logo u otras preferencias.';
$string['contentype']           = 'Tipo de contenido';
$string['pagina']               = 'Página web interactiva';
$string['slides']               = 'Presentación de diapositivas';
$string['generate']             = 'Generar contenido';
$string['generating']           = 'Generando contenido, por favor esperá...';

// Results
$string['result_html']          = 'Contenido interactivo';
$string['result_quiz']          = 'Autoevaluación';
$string['result_h5p']           = 'Actividad H5P';
$string['result_gift']          = 'Banco de preguntas';
$string['created_ok']           = 'Recursos creados exitosamente en el curso.';
$string['created_error']        = 'Hubo un error al crear algunos recursos. Revisá el log.';

// Errors
$string['error_nofile']         = 'Debés subir un archivo PDF o DOCX.';
$string['error_api']            = 'Error al conectar con la API de Doc2Interact.';
$string['error_credits']        = 'Créditos agotados. Contactá a Doc2Interact para recargar.';
$string['teacheronly']          = 'Esta actividad es solo para docentes.';
$string['notext']               = 'Debés subir un archivo y esperar que se extraiga el texto.';

// Privacy
$string['privacy:metadata:doc2interact_api']                  = 'Doc2Interact envía el texto del documento a su servidor API para generar contenido.';
$string['privacy:metadata:doc2interact_api:textoCompleto']    = 'El texto extraído del documento subido.';
$string['privacy:metadata:doc2interact_api:titulo']           = 'El título del contenido generado.';
