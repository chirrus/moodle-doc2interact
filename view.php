<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/doc2interact/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

$id = required_param('id', PARAM_INT);
list($course, $cm) = get_course_and_cm_from_cmid($id, 'doc2interact');
$instance = $DB->get_record('doc2interact', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$PAGE->set_url('/mod/doc2interact/view.php', ['id' => $id]);
$PAGE->set_title($instance->name);
$PAGE->set_heading($course->fullname);

$iseditor = has_capability('mod/doc2interact:generate', $context);

echo $OUTPUT->header();
echo $OUTPUT->heading($instance->name);

// ─── HELPER: agregar módulo a sección ────────────────────────────────────────
function d2i_agregar_a_seccion($course, $cm, $modname, $instanceid) {
    global $DB;
    $modid = $DB->get_field('modules', 'id', ['name' => $modname]);
    if (!$modid) throw new Exception("Módulo $modname no instalado en Moodle");

    $mod = new stdClass();
    $mod->course   = $course->id;
    $mod->module   = $modid;
    $mod->instance = $instanceid;
    $mod->section  = $DB->get_record('course_sections', ['id' => $cm->section])->id;
    $mod->visible  = 1;
    $mod->added    = time();
    $mod->id = $DB->insert_record('course_modules', $mod);

    // Refrescar sectionrow para tener sequence actualizado
    $sectionrow = $DB->get_record('course_sections', ['id' => $cm->section]);
    $seq = trim($sectionrow->sequence);
    $sectionrow->sequence = $seq ? $seq . ',' . $mod->id : (string)$mod->id;
    $DB->update_record('course_sections', $sectionrow);

    context_module::instance($mod->id);
    rebuild_course_cache($course->id, true);
    return $mod->id;
}

// ─── PROCESAMIENTO ───────────────────────────────────────────────────────────
if ($iseditor && optional_param('action', '', PARAM_ALPHA) === 'generate') {
    require_sesskey();

    $texto         = optional_param('texto', '', PARAM_RAW);
    $titulo        = optional_param('titulo', $instance->name, PARAM_TEXT);
    $tipo          = optional_param('tipocont', 'pagina', PARAM_ALPHA);
    $instrucciones = optional_param('instrucciones', '', PARAM_RAW);
    $apikey        = get_config('mod_doc2interact', 'apikey') ?: 'Moodle2026#!';
    $apiurl        = get_config('mod_doc2interact', 'apiurl') ?: 'https://doc2interact.com';

    if (empty(trim($texto))) {
        echo $OUTPUT->notification('Debés subir un archivo y esperar que se extraiga el texto.', 'error');
    } else {
        $payload = json_encode([
            'accessKey'     => $apikey,
            'textoCompleto' => $texto,
            'titulo'        => $titulo,
            'tipoContenido' => $tipo,
            'promptExtra'   => $instrucciones,
            'nombreBase'    => preg_replace('/[^a-z0-9_]/i', '_', $titulo),
            'logo'          => '',
            'colores'       => '',
            'moodlePlugin'  => true,
        ]);

        $ch = curl_init($apiurl . '/generar');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 180,
        ]);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlerr  = curl_error($ch);
        curl_close($ch);

        if ($curlerr || $httpcode !== 200) {
            echo $OUTPUT->notification('Error conectando con Doc2Interact: ' . ($curlerr ?: "HTTP $httpcode"), 'error');
        } else {
            $data = json_decode($response, true);
            if (!$data || isset($data['error'])) {
                echo $OUTPUT->notification('Error de la API: ' . ($data['error'] ?? 'Respuesta inválida'), 'error');
            } else {
                $errors  = [];
                $creados = [];

                // ── 1. HTML Contenido → Página ─────────────────────────────
                if (!empty($data['htmlContenido'])) {
                    try {
                        $page = new stdClass();
                        $page->course        = $course->id;
                        $page->name          = $titulo . ' — Contenido interactivo';
                        $page->intro         = '';
                        $page->introformat   = FORMAT_HTML;
                        $page->content       = $data['htmlContenido'];
                        $page->contentformat = FORMAT_HTML;
                        $page->display       = 0;
                        $page->timemodified  = time();
                        $page->id = $DB->insert_record('page', $page);
                        d2i_agregar_a_seccion($course, $cm, 'page', $page->id);
                        $creados[] = 'Contenido interactivo';
                    } catch (Exception $e) {
                        $errors[] = 'Contenido: ' . $e->getMessage();
                    }
                }

                // ── 2. HTML Autoevaluación → Página ───────────────────────
                if (!empty($data['htmlEvaluacion'])) {
                    try {
                        $page = new stdClass();
                        $page->course        = $course->id;
                        $page->name          = $titulo . ' — Autoevaluación';
                        $page->intro         = '';
                        $page->introformat   = FORMAT_HTML;
                        $page->content       = $data['htmlEvaluacion'];
                        $page->contentformat = FORMAT_HTML;
                        $page->display       = 0;
                        $page->timemodified  = time();
                        $page->id = $DB->insert_record('page', $page);
                        d2i_agregar_a_seccion($course, $cm, 'page', $page->id);
                        $creados[] = 'Autoevaluación';
                    } catch (Exception $e) {
                        $errors[] = 'Autoevaluación: ' . $e->getMessage();
                    }
                }

                // ── 3. Foro (Debate sencillo) ─────────────────────────────
                if (!empty($data['foro'])) {
                    try {
                        $intro_foro = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $data['foro']);

                        $forum = new stdClass();
                        $forum->course                = (int)$course->id;
                        $forum->type                  = 'single';
                        $forum->name                  = $titulo . ' — Foro';
                        $forum->intro                 = $intro_foro;
                        $forum->introformat           = FORMAT_HTML;
                        $forum->duedate               = 0;
                        $forum->cutoffdate            = 0;
                        $forum->assessed              = 0;
                        $forum->assesstimestart       = 0;
                        $forum->assesstimefinish      = 0;
                        $forum->scale                 = 0;
                        $forum->grade_forum           = 0;
                        $forum->grade_forum_notify    = 0;
                        $forum->maxbytes              = 512000;
                        $forum->maxattachments        = 9;
                        $forum->forcesubscribe        = 0;
                        $forum->trackingtype          = 1;
                        $forum->rsstype               = 0;
                        $forum->rssarticles           = 0;
                        $forum->timemodified          = time();
                        $forum->warnafter             = 0;
                        $forum->blockafter            = 0;
                        $forum->blockperiod           = 0;
                        $forum->completiondiscussions = 0;
                        $forum->completionreplies     = 0;
                        $forum->completionposts       = 0;
                        $forum->displaywordcount      = 0;
                        $forum->lockdiscussionafter   = 0;
                        $forum->id = $DB->insert_record('forum', $forum);
                        if (!$forum->id) throw new Exception('insert forum falló');

                        $cmid = d2i_agregar_a_seccion($course, $cm, 'forum', $forum->id);

                        // Crear discusión inicial (requerida por tipo 'single')
                        $discussion = new stdClass();
                        $discussion->course       = $course->id;
                        $discussion->forum        = $forum->id;
                        $discussion->name         = $titulo . ' — Foro';
                        $discussion->firstpost    = 0;
                        $discussion->userid       = $USER->id;
                        $discussion->groupid      = -1;
                        $discussion->assessed     = 0;
                        $discussion->timemodified = time();
                        $discussion->usermodified = $USER->id;
                        $discussion->timestart    = 0;
                        $discussion->timeend      = 0;
                        $discussion->pinned       = 0;
                        $discussion->timelocked   = 0;
                        $discussion->id = $DB->insert_record('forum_discussions', $discussion);

                        // Crear post inicial
                        $post = new stdClass();
                        $post->discussion    = $discussion->id;
                        $post->parent        = 0;
                        $post->userid        = $USER->id;
                        $post->created       = time();
                        $post->modified      = time();
                        $post->mailed        = 0;
                        $post->subject       = $titulo . ' — Foro';
                        $post->message       = $intro_foro;
                        $post->messageformat = FORMAT_HTML;
                        $post->messagetrust  = 0;
                        $post->attachment    = '';
                        $post->totalscore    = 0;
                        $post->mailnow       = 0;
                        $post->deleted       = 0;
                        $post->privatereplyto = 0;
                        $post->id = $DB->insert_record('forum_posts', $post);

                        // Actualizar firstpost
                        $DB->set_field('forum_discussions', 'firstpost', $post->id, ['id' => $discussion->id]);

                        $creados[] = 'Foro';
                    } catch (Exception $e) {
                        $di = property_exists($e, 'debuginfo') ? $e->debuginfo : '';
                        $errors[] = 'Foro: ' . $e->getMessage() . ' || ' . $di;
                    }
                }

                // ── 4. Tarea ──────────────────────────────────────────────
                if (!empty($data['tarea'])) {
                    try {
                        $assign = new stdClass();
                        $assign->course                      = (int)$course->id;
                        $assign->name                        = $titulo . ' — Tarea';
                        // Limpiar emojis (MySQL utf8 no soporta caracteres de 4 bytes)
                        $intro_tarea = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $data['tarea']);
                        $assign->intro = $intro_tarea;
                        $assign->introformat                 = FORMAT_HTML;
                        $assign->activity                    = '';
                        $assign->activityformat              = FORMAT_HTML;
                        $assign->submissionattachments       = 0;
                        $assign->alwaysshowdescription       = 1;
                        $assign->nosubmissions               = 0;
                        $assign->submissiondrafts            = 0;
                        $assign->sendnotifications           = 0;
                        $assign->sendlatenotifications       = 0;
                        $assign->sendstudentnotifications    = 1;
                        $assign->duedate                     = 0;
                        $assign->allowsubmissionsfromdate    = 0;
                        $assign->grade                       = 100;
                        $assign->timemodified                = time();
                        $assign->requiresubmissionstatement = 0;
                        $assign->completionsubmit            = 0;
                        $assign->cutoffdate                  = 0;
                        $assign->timelimit                   = 0;
                        $assign->gradingduedate              = 0;
                        $assign->teamsubmission              = 0;
                        $assign->requireallteammemberssubmit = 0;
                        $assign->teamsubmissiongroupingid    = 0;
                        $assign->blindmarking                = 0;
                        $assign->hidegrader                  = 0;
                        $assign->revealidentities            = 0;
                        $assign->attemptreopenmethod         = 'untilpass';
                        $assign->maxattempts                 = 1;
                        $assign->markingworkflow             = 0;
                        $assign->markingallocation           = 0;
                        $assign->markinganonymous            = 0;
                        $assign->preventsubmissionnotingroup = 0;

                        $assign->id = $DB->insert_record('assign', $assign);
                        if (!$assign->id) throw new Exception('insert_record assign falló');

                        d2i_agregar_a_seccion($course, $cm, 'assign', $assign->id);

                        // Habilitar entrega de archivo y texto en línea
                        $DB->delete_records('assign_plugin_config', ['assignment' => $assign->id]);
                        foreach ([['assignsubmission','file','enabled','1'],
                                  ['assignsubmission','onlinetext','enabled','1'],
                                  ['assignsubmission','file','maxfilesubmissions','1'],
                                  ['assignsubmission','file','maxsubmissionsizebytes','0']] as $pc) {
                            $p = new stdClass();
                            $p->assignment = $assign->id;
                            $p->subtype    = $pc[0];
                            $p->plugin     = $pc[1];
                            $p->name       = $pc[2];
                            $p->value      = $pc[3];
                            $DB->insert_record('assign_plugin_config', $p);
                        }
                        $creados[] = 'Tarea';
                    } catch (Exception $e) {
                        $di = property_exists($e, 'debuginfo') ? $e->debuginfo : 'sin debuginfo';
                        $errors[] = 'Tarea: ' . $e->getMessage() . ' || ' . $di;
                    }
                }

                // ── 5. Banco de preguntas (estructura Moodle 4.x) ─────────
                if (!empty($data['giftTexto'])) {
                    try {
                        require_once($CFG->dirroot . '/lib/questionlib.php');
                        require_once($CFG->dirroot . '/question/engine/bank.php');

                        $context_course = context_course::instance($course->id);

                        // Categoría padre = nombre del plugin
                        $cat_padre = $DB->get_record('question_categories', [
                            'name'      => $instance->name,
                            'contextid' => $context_course->id,
                        ]);
                        if (!$cat_padre) {
                            $cat_padre = new stdClass();
                            $cat_padre->name       = $instance->name;
                            $cat_padre->contextid  = $context_course->id;
                            $cat_padre->info       = 'Generado por Doc2Interact';
                            $cat_padre->infoformat = FORMAT_HTML;
                            $cat_padre->parent     = question_get_default_category($context_course->id, true)->id;
                            $cat_padre->sortorder  = 999;
                            $cat_padre->stamp      = make_unique_id_code();
                            $cat_padre->id = $DB->insert_record('question_categories', $cat_padre);
                        }

                        // Subcategoría = título del contenido generado
                        $cat = new stdClass();
                        $cat->name       = $titulo;
                        $cat->contextid  = $context_course->id;
                        $cat->info       = '';
                        $cat->infoformat = FORMAT_HTML;
                        $cat->parent     = $cat_padre->id;
                        $cat->sortorder  = 999;
                        $cat->stamp      = make_unique_id_code();
                        $cat->id = $DB->insert_record('question_categories', $cat);

                        // Parsear GIFT — soporta MCH, V/F y completar
                        $texto = preg_replace('/\/\/[^\n]*\n/', "\n", $data['giftTexto']);
                        $texto = preg_replace('/\/\/[^\n]*$/', '', $texto);
                        $bloques = preg_split('/\n\s*\n/', trim($texto));
                        $importadas = 0;

                        $contadorMCH = 0;
                        $contadorVF  = 0;
                        $contadorSA  = 0;

                        foreach ($bloques as $bloque) {
                            $bloque = trim($bloque);
                            if (empty($bloque)) continue;
                            if (!preg_match('/^::([^:]+)::\s*(.*?)\s*\{(.+)\}$/s', $bloque, $m)) continue;

                            $enunciado = trim($m[2]);
                            $opciones  = trim($m[3]);

                            // Detectar tipo de pregunta
                            $qtype = 'multichoice';
                            $opciones_norm = strtolower(trim($opciones));

                            if (preg_match('/^(true|false|verdadero|falso|t|f)$/i', $opciones_norm)) {
                                $qtype = 'truefalse';
                                $contadorVF++;
                                $nombre = $titulo . ' — V/F ' . $contadorVF;
                            } elseif (strpos($opciones, '=') === false && strpos($opciones, '~') === false) {
                                $qtype = 'shortanswer';
                                $contadorSA++;
                                $nombre = $titulo . ' — Completar ' . $contadorSA;
                            } else {
                                $contadorMCH++;
                                $nombre = $titulo . ' — MCH ' . $contadorMCH;
                            }

                            // Crear question_bank_entry
                            $qbe = new stdClass();
                            $qbe->questioncategoryid = $cat->id;
                            $qbe->idnumber           = null;
                            $qbe->ownerid            = $USER->id;
                            $qbe->id = $DB->insert_record('question_bank_entries', $qbe);

                            // Crear question
                            $q = new stdClass();
                            $q->category              = $cat->id;
                            $q->parent                = 0;
                            $q->name                  = $nombre;
                            $q->questiontext          = '<p>' . s($enunciado) . '</p>';
                            $q->questiontextformat    = FORMAT_HTML;
                            $q->generalfeedback       = '';
                            $q->generalfeedbackformat = FORMAT_HTML;
                            $q->defaultmark           = 1;
                            $q->penalty               = ($qtype === 'multichoice') ? 0.3333333 : 1.0;
                            $q->qtype                 = $qtype;
                            $q->length                = 1;
                            $q->stamp                 = make_unique_id_code();
                            $q->version               = make_unique_id_code();
                            $q->hidden                = 0;
                            $q->idnumber              = null;
                            $q->timecreated           = time();
                            $q->timemodified          = time();
                            $q->createdby             = $USER->id;
                            $q->modifiedby            = $USER->id;
                            $q->id = $DB->insert_record('question', $q);

                            // Crear question_versions
                            $qv = new stdClass();
                            $qv->questionbankentryid = $qbe->id;
                            $qv->version             = 1;
                            $qv->questionid          = $q->id;
                            $qv->status              = 'ready';
                            $DB->insert_record('question_versions', $qv);

                            if ($qtype === 'multichoice') {
                                // Opciones multichoice
                                $qmc = new stdClass();
                                $qmc->questionid                     = $q->id;
                                $qmc->layout                         = 0;
                                $qmc->single                         = 1;
                                $qmc->shuffleanswers                 = 1;
                                $qmc->correctfeedback                = '<p>Correcto.</p>';
                                $qmc->correctfeedbackformat          = FORMAT_HTML;
                                $qmc->partiallycorrectfeedback       = '';
                                $qmc->partiallycorrectfeedbackformat = FORMAT_HTML;
                                $qmc->incorrectfeedback              = '<p>Incorrecto.</p>';
                                $qmc->incorrectfeedbackformat        = FORMAT_HTML;
                                $qmc->answernumbering                = 'abc';
                                $qmc->shownumcorrect                 = 0;
                                $DB->insert_record('qtype_multichoice_options', $qmc);

                                preg_match_all('/([=~])([^=~#\n]+)/', $opciones, $rm, PREG_SET_ORDER);
                                foreach ($rm as $r) {
                                    $ans = new stdClass();
                                    $ans->question      = $q->id;
                                    $ans->answer        = '<p>' . s(trim($r[2])) . '</p>';
                                    $ans->answerformat  = FORMAT_HTML;
                                    $ans->fraction      = ($r[1] === '=') ? 1.0 : 0.0;
                                    $ans->feedback      = '';
                                    $ans->feedbackformat = FORMAT_HTML;
                                    $DB->insert_record('question_answers', $ans);
                                }

                            } elseif ($qtype === 'truefalse') {
                                // Verdadero/Falso
                                $esVerdadero = preg_match('/^(true|verdadero|t)$/i', $opciones_norm);

                                $qdb = new stdClass();
                                $qdb->question       = $q->id;
                                $qdb->trueanswerfeedback  = '';
                                $qdb->trueanswerfeedbackformat = FORMAT_HTML;
                                $qdb->falseanswerfeedback = '';
                                $qdb->falseanswerfeedbackformat = FORMAT_HTML;
                                $qdb->showstandardinstruction = 0;

                                // Respuesta verdadera
                                $ansT = new stdClass();
                                $ansT->question      = $q->id;
                                $ansT->answer        = 'Verdadero';
                                $ansT->answerformat  = FORMAT_PLAIN;
                                $ansT->fraction      = $esVerdadero ? 1.0 : 0.0;
                                $ansT->feedback      = '';
                                $ansT->feedbackformat = FORMAT_HTML;
                                $ansT->id = $DB->insert_record('question_answers', $ansT);

                                // Respuesta falsa
                                $ansF = new stdClass();
                                $ansF->question      = $q->id;
                                $ansF->answer        = 'Falso';
                                $ansF->answerformat  = FORMAT_PLAIN;
                                $ansF->fraction      = $esVerdadero ? 0.0 : 1.0;
                                $ansF->feedback      = '';
                                $ansF->feedbackformat = FORMAT_HTML;
                                $ansF->id = $DB->insert_record('question_answers', $ansF);

                                $qdb->trueanswer  = $ansT->id;
                                $qdb->falseanswer = $ansF->id;
                                $DB->insert_record('question_truefalse', $qdb);

                            } elseif ($qtype === 'shortanswer') {
                                // Respuesta corta
                                $qsa = new stdClass();
                                $qsa->questionid   = $q->id;
                                $qsa->usecase      = 0;
                                $DB->insert_record('qtype_shortanswer_options', $qsa);

                                // Extraer respuesta correcta del GIFT: {=respuesta}
                                preg_match('/=([^}#]+)/', $opciones, $sa_m);
                                $respuesta = isset($sa_m[1]) ? trim($sa_m[1]) : trim($opciones, '= ');
                                $ans = new stdClass();
                                $ans->question      = $q->id;
                                $ans->answer        = $respuesta;
                                $ans->answerformat  = FORMAT_PLAIN;
                                $ans->fraction      = 1.0;
                                $ans->feedback      = '';
                                $ans->feedbackformat = FORMAT_HTML;
                                $DB->insert_record('question_answers', $ans);
                            }

                            $importadas++;
                        }

                        if ($importadas === 0) throw new Exception('No se encontraron preguntas válidas');
                        $creados[] = "Banco de preguntas ($importadas preguntas en '$titulo')";

                    } catch (Exception $e) {
                        $errors[] = 'Banco de preguntas: ' . $e->getMessage();
                    }
                }

                // ── 6. H5P ────────────────────────────────────────────────
                if (!empty($data['h5pBase64'])) {
                    try {
                        $h5pcontent = base64_decode($data['h5pBase64']);
                        $tmpfile    = $CFG->tempdir . '/h5p_' . uniqid() . '.h5p';
                        file_put_contents($tmpfile, $h5pcontent);

                        $h5p = new stdClass();
                        $h5p->course       = $course->id;
                        $h5p->name         = $titulo . ' — Actividad H5P';
                        $h5p->intro        = '';
                        $h5p->introformat  = FORMAT_HTML;
                        $h5p->timecreated  = time();
                        $h5p->timemodified = time();
                        $h5p->id = $DB->insert_record('h5pactivity', $h5p);

                        $modcmid = d2i_agregar_a_seccion($course, $cm, 'h5pactivity', $h5p->id);
                        $modcontext = context_module::instance($modcmid);

                        $fs = get_file_storage();
                        $fileinfo = [
                            'contextid' => $modcontext->id,
                            'component' => 'mod_h5pactivity',
                            'filearea'  => 'package',
                            'itemid'    => 0,
                            'filepath'  => '/',
                            'filename'  => clean_filename($titulo) . '.h5p',
                        ];
                        $fs->create_file_from_pathname($fileinfo, $tmpfile);
                        @unlink($tmpfile);
                        $creados[] = 'Actividad H5P';
                    } catch (Exception $e) {
                        $errors[] = 'H5P: ' . $e->getMessage();
                    }
                }

                // ── Resultado ─────────────────────────────────────────────
                if (!empty($creados)) {
                    echo $OUTPUT->notification('✅ Recursos creados: ' . implode(', ', $creados), 'success');
                }
                if (!empty($errors)) {
                    $nombresError = array_map(function($e) { return explode(':', $e)[0]; }, $errors);
                    echo $OUTPUT->notification('⚠️ Algunos recursos no pudieron crearse: ' . implode(', ', $nombresError), 'warning');
                }
                echo '<p style="margin-top:1rem;"><a href="' . new moodle_url('/course/view.php', ['id' => $course->id]) . '" class="btn btn-primary">Ver el curso</a></p>';
            }
        }
    }
}

// ─── PANEL ───────────────────────────────────────────────────────────────────
if ($iseditor && optional_param('action', '', PARAM_ALPHA) !== 'generate') {
    $actionurl = new moodle_url('/mod/doc2interact/view.php', ['id' => $id, 'action' => 'generate', 'sesskey' => sesskey()]);
    ?>
    <div style="max-width:640px;margin:0 auto;font-family:sans-serif;">
      <p style="color:#555;margin-bottom:1.5rem;">
        Subí un documento PDF o DOCX. Doc2Interact generará el contenido y lo agregará directamente a este curso.
      </p>
      <form method="post" action="<?php echo $actionurl; ?>">

        <div style="margin-bottom:1rem;">
          <label style="display:block;font-weight:600;margin-bottom:.4rem;">Título del contenido</label>
          <input type="text" name="titulo" value="<?php echo s($instance->name); ?>"
                 style="width:100%;padding:.5rem;border:1px solid #ccc;border-radius:4px;" />
        </div>

        <div style="margin-bottom:1rem;">
          <label style="display:block;font-weight:600;margin-bottom:.4rem;">Tipo de contenido</label>
          <select name="tipocont" style="padding:.5rem;border:1px solid #ccc;border-radius:4px;">
            <option value="pagina">Página web interactiva</option>
            <option value="slides">Presentación de diapositivas</option>
          </select>
        </div>

        <div style="margin-bottom:1rem;">
          <label style="display:block;font-weight:600;margin-bottom:.4rem;">Subir documento (PDF o DOCX)</label>
          <p style="font-size:.85rem;color:#888;margin-bottom:.5rem;">El texto se extrae en tu navegador. Tu archivo no se sube al servidor.</p>
          <input type="file" id="docfile" accept=".pdf,.docx" style="display:block;margin-bottom:.5rem;" />
          <textarea name="texto" id="textoExtraido" rows="5"
                    placeholder="El texto extraído aparecerá aquí antes de enviar..."
                    style="width:100%;padding:.5rem;border:1px solid #ccc;border-radius:4px;font-size:.85rem;color:#555;"></textarea>
        </div>

        <div style="margin-bottom:1.5rem;">
          <label style="display:block;font-weight:600;margin-bottom:.4rem;">Instrucciones adicionales (opcional)</label>
          <textarea name="instrucciones" rows="3"
                    placeholder="Ej: colores azul y dorado, fuente Montserrat..."
                    style="width:100%;padding:.5rem;border:1px solid #ccc;border-radius:4px;"></textarea>
        </div>

        <button type="submit" id="btnGenerar"
                style="background:#1a1a2e;color:#fff;padding:.75rem 2rem;border:none;border-radius:6px;font-size:1rem;cursor:pointer;">
          Generar contenido
        </button>
      </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js"></script>
    <script>
    document.getElementById('docfile').addEventListener('change', async function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const textarea = document.getElementById('textoExtraido');
        textarea.value = 'Extrayendo texto...';
        const ext = file.name.split('.').pop().toLowerCase();
        try {
            if (ext === 'pdf') {
                pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                const ab = await file.arrayBuffer();
                const pdf = await pdfjsLib.getDocument({data: ab}).promise;
                let text = '';
                for (let i = 1; i <= pdf.numPages; i++) {
                    const page = await pdf.getPage(i);
                    const content = await page.getTextContent();
                    text += content.items.map(s => s.str).join(' ') + '\n';
                }
                textarea.value = text.trim();
            } else if (ext === 'docx') {
                const ab = await file.arrayBuffer();
                const result = await mammoth.extractRawText({arrayBuffer: ab});
                textarea.value = result.value.trim();
            }
        } catch(err) {
            textarea.value = '';
            alert('No se pudo extraer el texto: ' + err.message);
        }
    });
    document.querySelector('form').addEventListener('submit', function() {
        const btn = document.getElementById('btnGenerar');
        btn.disabled = true;
        btn.textContent = 'Generando, por favor esperá...';
    });
    </script>
    <?php
} elseif (!$iseditor) {
    echo $OUTPUT->notification('Esta actividad es solo para docentes.', 'info');
}

echo $OUTPUT->footer();
