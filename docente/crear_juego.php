<?php
/**
 * Crear nuevo juego educativo (docente)
 * - Asignatura = nombre de la materia (solo lectura)
 * - Preguntas editables manualmente O generables con IA
 * - Fechas de apertura y cierre
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('docente');

$db = getDB();
$userId = $_SESSION['user_id'];
$materiaId = intval($_GET['materia_id'] ?? 0);
$materia = getMateriaById($materiaId);
if (!$materia) {
    setFlashMessage('danger', 'Materia no encontrada.');
    header('Location: ' . BASE_URL . '/docente/index.php');
    exit;
}

$theme = getMateriaThemeClass($materia['nombre']);
$asignatura = $materia['nombre']; // fija, viene de la materia

// Variables del formulario
$titulo = '';
$tema = '';
$grado = '';
$paralelo = '';
$cantidad = 5;
$fechaApertura = '';
$fechaFin = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errores[] = 'Token CSRF inválido.';
    }
    $titulo         = trim($_POST['titulo'] ?? '');
    $tema           = trim($_POST['tema'] ?? '');
    $grado          = trim($_POST['grado'] ?? '');
    $paralelo       = trim($_POST['paralelo'] ?? '');
    $cantidad       = intval($_POST['cantidad'] ?? 5);
    $fechaApertura  = trim($_POST['fecha_apertura'] ?? '');
    $fechaFin       = trim($_POST['fecha_fin'] ?? '');

    if ($titulo === '') $errores[] = 'El título es obligatorio.';
    if ($tema === '')   $errores[] = 'El tema es obligatorio.';
    if ($grado === '')  $errores[] = 'Debe seleccionar un curso.';
    if ($paralelo === '') $errores[] = 'Debe seleccionar un paralelo.';
    if ($fechaApertura === '') $errores[] = 'La fecha de apertura es obligatoria.';
    if ($fechaFin === '') $errores[] = 'La fecha de cierre es obligatoria.';
    if ($fechaApertura && $fechaFin && $fechaFin <= $fechaApertura) {
        $errores[] = 'La fecha de cierre debe ser posterior a la de apertura.';
    }
    if ($cantidad < 5 || $cantidad > 10) {
        $errores[] = 'La cantidad de preguntas debe estar entre 5 y 10.';
    }

    // Recoger preguntas del formulario (editables)
    $preguntasPost = [];
    for ($i = 0; $i < $cantidad; $i++) {
        $pq = trim($_POST["pregunta_texto_$i"] ?? '');
        $pa = trim($_POST["pregunta_a_$i"] ?? '');
        $pb = trim($_POST["pregunta_b_$i"] ?? '');
        $pc = trim($_POST["pregunta_c_$i"] ?? '');
        $pd = trim($_POST["pregunta_d_$i"] ?? '');
        $pr = trim($_POST["pregunta_correcta_$i"] ?? '');
        if ($pq === '' || $pa === '' || $pb === '' || $pc === '' || $pd === '' || $pr === '') {
            continue; // vacía, la ignoramos
        }
        $preguntasPost[] = [
            'pregunta' => $pq,
            'opcion_a' => $pa,
            'opcion_b' => $pb,
            'opcion_c' => $pc,
            'opcion_d' => $pd,
            'respuesta_correcta' => strtoupper($pr)
        ];
    }

    if (empty($preguntasPost)) {
        $errores[] = 'Debe tener al menos una pregunta completa.';
    }

    if (empty($errores)) {
        try {
            $stmt = $db->prepare("INSERT INTO juegos (materia_id, docente_id, titulo, asignatura, tema, grado, paralelo, fecha_apertura, fecha_fin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$materiaId, $userId, $titulo, $asignatura, $tema, $grado, $paralelo, $fechaApertura, $fechaFin]);
            $juegoId = $db->lastInsertId();

            $insertStmt = $db->prepare("INSERT INTO juegos_preguntas (juego_id, pregunta, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($preguntasPost as $p) {
                $insertStmt->execute([
                    $juegoId,
                    $p['pregunta'],
                    $p['opcion_a'],
                    $p['opcion_b'],
                    $p['opcion_c'],
                    $p['opcion_d'],
                    $p['respuesta_correcta']
                ]);
            }

            setFlashMessage('success', 'Juego creado correctamente con ' . count($preguntasPost) . ' preguntas.');
            header('Location: juegos.php?materia_id=' . $materiaId);
            exit;
        } catch (PDOException $e) {
            $errores[] = 'Error al crear el juego: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<style>
    .pregunta-card { border: 1px solid #dee2e6; border-radius: 8px; padding: 16px; margin-bottom: 12px; background: #fdfdfd; transition: box-shadow .2s; }
    .pregunta-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
    .pregunta-card .pregunta-num { font-weight: 700; color: #0d6efd; font-size: 1rem; }
    .opcion-correcta { border: 2px solid #198754 !important; background: #d1e7dd !important; }
    #btnGenerarIA { transition: all .3s; }
    #btnGenerarIA:hover { transform: scale(1.03); }
</style>

<div class="container-fluid px-4 py-4 <?= $theme ?>">
    <h2 class="mb-4"><i class="bi bi-plus-circle me-2"></i>Crear Nuevo Juego Educativo</h2>

    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errores as $error): ?>
                    <li><?= sanitize($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="crear_juego.php?materia_id=<?= $materiaId ?>" id="formCrearJuego" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <!-- Título -->
        <div class="col-md-6">
            <label class="form-label fw-semibold">Título del Juego</label>
            <input type="text" name="titulo" id="inputTitulo" class="form-control" value="<?= sanitize($titulo) ?>" placeholder="Ej: Aventura Matemática" required>
        </div>

        <!-- Asignatura (solo lectura, de la materia) -->
        <div class="col-md-6">
            <label class="form-label fw-semibold">Asignatura</label>
            <input type="text" class="form-control bg-light" value="<?= sanitize($asignatura) ?>" readonly>
            <input type="hidden" name="asignatura" value="<?= sanitize($asignatura) ?>">
        </div>

        <!-- Curso y Paralelo -->
        <div class="col-md-3">
            <label class="form-label fw-semibold">Curso</label>
            <select name="grado" id="inputGrado" class="form-select" required>
                <option value="">Seleccionar...</option>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>" <?= $grado == $i ? 'selected' : '' ?>><?= $i ?>° Grado</option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Paralelo</label>
            <select name="paralelo" id="inputParalelo" class="form-select" required>
                <option value="">Seleccionar...</option>
                <?php foreach (['A','B','C','D'] as $p): ?>
                    <option value="<?= $p ?>" <?= $paralelo === $p ? 'selected' : '' ?>><?= $p ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Tema -->
        <div class="col-md-6">
            <label class="form-label fw-semibold">Tema</label>
            <input type="text" name="tema" id="inputTema" class="form-control" value="<?= sanitize($tema) ?>" placeholder="Ej: Fracciones equivalentes" required>
        </div>

        <!-- Fechas -->
        <div class="col-md-3">
            <label class="form-label fw-semibold"><i class="bi bi-calendar-event me-1"></i>Fecha de Apertura</label>
            <input type="datetime-local" name="fecha_apertura" id="inputFechaApertura" class="form-control" value="<?= sanitize($fechaApertura) ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold"><i class="bi bi-calendar-x me-1"></i>Fecha de Cierre</label>
            <input type="datetime-local" name="fecha_fin" id="inputFechaFin" class="form-control" value="<?= sanitize($fechaFin) ?>" required>
        </div>

        <!-- Cantidad de preguntas + Botón IA -->
        <div class="col-md-3">
            <label class="form-label fw-semibold">Cantidad de Preguntas (5‑10)</label>
            <input type="number" name="cantidad" id="inputCantidad" class="form-control" min="5" max="10" value="<?= $cantidad ?>" required>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="button" id="btnGenerarIA" class="btn btn-warning w-100 py-2" onclick="generarConIA()">
                <i class="bi bi-stars me-1"></i>Generar con IA
            </button>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button type="button" class="btn btn-outline-primary w-100 py-2" onclick="mostrarPreguntasVacias()">
                <i class="bi bi-pencil-square me-1"></i>Crear manualmente
            </button>
        </div>

        <!-- Contenedor de preguntas editables -->
        <div class="col-12 mt-3" id="preguntasEditables" style="display:none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="bi bi-list-check me-1"></i>Preguntas del Juego</h5>
                <span class="badge bg-primary" id="contadorPreguntas">0 preguntas</span>
            </div>
            <div id="listadoPreguntas">
                <!-- Las preguntas se renderizan aquí (JS) -->
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="col-12 mt-3">
            <button type="submit" class="btn btn-success btn-lg" id="btnCrear">
                <i class="bi bi-check-circle me-1"></i>Crear Juego
            </button>
            <a href="juegos.php?materia_id=<?= $materiaId ?>" class="btn btn-outline-secondary btn-lg ms-2">Cancelar</a>
        </div>
    </form>
</div>

<script>
const BASE = '<?= BASE_URL ?>';
const CSRF = '<?= $_SESSION['csrf_token'] ?>';

// ─── Renderizar N preguntas editables (vacías o pre-rellenadas) ───
function renderizarPreguntas(preguntas) {
    const container = document.getElementById('listadoPreguntas');
    container.innerHTML = '';
    preguntas.forEach((p, i) => {
        container.appendChild(crearCardPregunta(i, p));
    });
    document.getElementById('preguntasEditables').style.display = 'block';
    document.getElementById('contadorPreguntas').textContent = preguntas.length + ' preguntas';
}

function crearCardPregunta(idx, data) {
    const div = document.createElement('div');
    div.className = 'pregunta-card';
    div.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="pregunta-num">Pregunta ${idx + 1}</span>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.pregunta-card').remove(); actualizarContador();" title="Eliminar pregunta">
                <i class="bi bi-trash"></i>
            </button>
        </div>
        <div class="mb-2">
            <label class="form-label small text-muted">Enunciado</label>
            <textarea name="pregunta_texto_${idx}" class="form-control" rows="2" required>${esc(data.pregunta || '')}</textarea>
        </div>
        <div class="row g-2">
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text fw-bold ${data.respuesta_correcta === 'A' ? 'bg-success text-white' : ''}">A</span>
                    <input type="text" name="pregunta_a_${idx}" class="form-control ${data.respuesta_correcta === 'A' ? 'opcion-correcta' : ''}" value="${esc(data.opcion_a || '')}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text fw-bold ${data.respuesta_correcta === 'B' ? 'bg-success text-white' : ''}">B</span>
                    <input type="text" name="pregunta_b_${idx}" class="form-control ${data.respuesta_correcta === 'B' ? 'opcion-correcta' : ''}" value="${esc(data.opcion_b || '')}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text fw-bold ${data.respuesta_correcta === 'C' ? 'bg-success text-white' : ''}">C</span>
                    <input type="text" name="pregunta_c_${idx}" class="form-control ${data.respuesta_correcta === 'C' ? 'opcion-correcta' : ''}" value="${esc(data.opcion_c || '')}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text fw-bold ${data.respuesta_correcta === 'D' ? 'bg-success text-white' : ''}">D</span>
                    <input type="text" name="pregunta_d_${idx}" class="form-control ${data.respuesta_correcta === 'D' ? 'opcion-correcta' : ''}" value="${esc(data.opcion_d || '')}" required>
                </div>
            </div>
        </div>
        <div class="mt-2">
            <label class="form-label small text-muted">Respuesta correcta</label>
            <select name="pregunta_correcta_${idx}" class="form-select form-select-sm w-auto" required>
                <option value="">--</option>
                <option value="A" ${data.respuesta_correcta === 'A' ? 'selected' : ''}>A</option>
                <option value="B" ${data.respuesta_correcta === 'B' ? 'selected' : ''}>B</option>
                <option value="C" ${data.respuesta_correcta === 'C' ? 'selected' : ''}>C</option>
                <option value="D" ${data.respuesta_correcta === 'D' ? 'selected' : ''}>D</option>
            </select>
        </div>
    `;
    return div;
}

function actualizarContador() {
    const n = document.querySelectorAll('.pregunta-card').length;
    document.getElementById('contadorPreguntas').textContent = n + ' preguntas';
}

// ─── Crear preguntas vacías para edición manual ───
function mostrarPreguntasVacias() {
    const cant = parseInt(document.getElementById('inputCantidad').value) || 5;
    const vacias = [];
    for (let i = 0; i < cant; i++) {
        vacias.push({ pregunta:'', opcion_a:'', opcion_b:'', opcion_c:'', opcion_d:'', respuesta_correcta:'' });
    }
    renderizarPreguntas(vacias);
}

// ─── Generar preguntas con IA y rellenar los campos editables ───
async function generarConIA() {
    const asignatura = '<?= addslashes($asignatura) ?>';
    const tema = document.getElementById('inputTema').value.trim();
    const cantidad = parseInt(document.getElementById('inputCantidad').value) || 5;

    if (!tema) {
        alert('Por favor, escribe el Tema antes de generar con IA.');
        return;
    }

    const btn = document.getElementById('btnGenerarIA');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generando...';

    try {
        const res = await fetch(BASE + '/api/generar_preguntas_juego.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            body: JSON.stringify({ asignatura, tema, cantidad })
        });

        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.error || 'Error HTTP ' + res.status);
        }

        const preguntas = await res.json();
        if (!Array.isArray(preguntas) || preguntas.length === 0) {
            throw new Error('La IA no devolvió preguntas válidas.');
        }

        // Renderizar como campos editables
        renderizarPreguntas(preguntas);

    } catch (err) {
        alert('Error al generar preguntas: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = orig;
    }
}

function esc(text) {
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
