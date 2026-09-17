<?php
/**
 * Juego del laberinto aleatorio para estudiantes
 * Cada intento genera un nuevo laberinto y un nuevo conjunto de preguntas (5‑10)
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('estudiante');

$db = getDB();
$userId = $_SESSION['user_id'];
$materiaId = intval($_GET['materia_id'] ?? 0);
$materia = getMateriaById($materiaId);
if (!$materia) {
    setFlashMessage('danger', 'Materia no encontrada.');
    header('Location: ' . BASE_URL . '/estudiante/index.php');
    exit;
}

// Obtener sesión activa para este grado/paralelo del estudiante
$grado = $_SESSION['grado'] ?? '';
$paralelo = $_SESSION['paralelo'] ?? '';

$stmt = $db->prepare("SELECT js.* FROM juegos_sesiones js
    JOIN juegos j ON js.juego_id = j.id
    WHERE j.materia_id = ? AND j.docente_id = ? AND (js.grado = ? OR js.grado = '')");
$stmt->execute([$materiaId, $materia['docente_id'], $grado]);
$sesion = $stmt->fetch();
if (!$sesion) {
    setFlashMessage('warning', 'No hay una sesión de juego activa para esta materia.');
    header('Location: ../estudiante/materia.php?id=' . $materiaId);
    exit;
}

$juegoId = $sesion['juego_id'];

// ---------- Generar laberinto aleatorio ----------
function generarLaberinto($n = 10, $m = 10) {
    // Inicializar cuadrícula con paredes (0 = pared, 1 = camino)
    $grid = array_fill(0, $n, array_fill(0, $m, 0));
    $stack = [];
    $dirs = [[-1,0],[1,0],[0,-1],[0,1]];
    // punto de partida aleatorio en celdas impares
    $cx = rand(0, $n-1);
    $cy = rand(0, $m-1);
    $grid[$cx][$cy] = 1;
    $stack[] = [$cx,$cy];
    while (!empty($stack)) {
        [$x,$y] = $stack[count($stack)-1];
        $neighbours = [];
        foreach ($dirs as $d) {
            $nx = $x + $d[0]*2;
            $ny = $y + $d[1]*2;
            if ($nx>=0 && $nx<$n && $ny>=0 && $ny<$m && $grid[$nx][$ny]==0) {
                $neighbours[] = $d;
            }
        }
        if (empty($neighbours)) {
            array_pop($stack);
        } else {
            $d = $neighbours[array_rand($neighbours)];
            $mx = $x + $d[0];
            $my = $y + $d[1];
            $nx = $x + $d[0]*2;
            $ny = $y + $d[1]*2;
            $grid[$mx][$my] = 1;
            $grid[$nx][$ny] = 1;
            $stack[] = [$nx,$ny];
        }
    }
    return $grid;
}

$laberinto = generarLaberinto(15,15); // 15x15 grid

// Si el estudiante envía respuestas, procesar intento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        setFlashMessage('danger', 'Token CSRF inválido.');
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
    $respuestas = $_POST['respuesta'] ?? [];
    $correctas = json_decode($_POST['correctas_json'] ?? '[]', true);
    $puntaje = 0;
    foreach ($correctas as $qid => $correcta) {
        $ans = $respuestas[$qid] ?? '';
        if (strtoupper($ans) === strtoupper($correcta)) {
            $puntaje++;
        }
    }
    // Guardar intento
    $stmt = $db->prepare("INSERT INTO juegos_intentos (sesion_id, estudiante_id, puntaje, fecha_intento) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$sesion['id'], $userId, $puntaje]);
    setFlashMessage('success', "Intento guardado. Puntaje: $puntaje / " . count($correctas));
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

$theme = getMateriaThemeClass($materia['nombre']);
include __DIR__ . '/../includes/header.php';
?>
<div class="container-fluid px-4 py-4 <?php echo $theme; ?>">
    <h2 class="mb-4"><i class="bi bi-joystick me-2"></i>Labertinto del Conocimiento</h2>
    <div id="laberintoContainer" class="mb-4" style="overflow:auto; max-height:400px; border:2px solid #ddd; border-radius:8px; background:#fafafa;"></div>
    <form method="post" id="formPreguntas" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div id="preguntasContainer"></div>
        <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-save me-1"></i>Enviar respuestas</button>
    </form>
</div>
<script>
const laberinto = <?php echo json_encode($laberinto); ?>;
const rows = laberinto.length;
const cols = laberinto[0].length;
const size = 30; // px por celda
const container = document.getElementById('laberintoContainer');
const canvas = document.createElement('canvas');
canvas.width = cols * size;
canvas.height = rows * size;
container.appendChild(canvas);
const ctx = canvas.getContext('2d');

function dibujar() {
    ctx.clearRect(0,0,canvas.width,canvas.height);
    for(let i=0;i<rows;i++){
        for(let j=0;j<cols;j++){
            if(laberinto[i][j]===0){
                ctx.fillStyle = '#333';
                ctx.fillRect(j*size,i*size,size,size);
            } else {
                ctx.fillStyle = '#fff';
                ctx.fillRect(j*size,i*size,size,size);
            }
        }
    }
    // jugador inicio en celda superior izquierda (buscamos primer camino)
    let startX=0,startY=0;
    outer: for(let i=0;i<rows;i++){
        for(let j=0;j<cols;j++){
            if(laberinto[i][j]===1){startY=i; startX=j; break outer;}
        }
    }
    jugador.x = startX;
    jugador.y = startY;
    dibujarJugador();
}

const jugador = {x:0,y:0};
function dibujarJugador(){
    ctx.fillStyle = '#ff5722';
    ctx.fillRect(jugador.x*size, jugador.y*size, size, size);
}

function mover(dx,dy){
    const nx = jugador.x + dx;
    const ny = jugador.y + dy;
    if(nx<0||ny<0||nx>=cols||ny>=rows) return;
    if(laberinto[ny][nx]===0) return; // pared
    jugador.x = nx; jugador.y = ny;
    dibujar();
    // Si llega al borde derecho inferior, consideramos final
    if(jugador.x===cols-1 && jugador.y===rows-1){
        setTimeout(iniciarPreguntas,200);
    }
}

function iniciarPreguntas(){
    // Ocultar laberinto y mostrar preguntas
    container.style.display='none';
    const form = document.getElementById('formPreguntas');
    form.style.display='block';
    fetch('<?php echo BASE_URL; ?>/api/generar_preguntas_juego.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({
            asignatura: '<?php echo addslashes($materia['asignatura'] ?? ''); ?>',
            tema: '<?php echo addslashes($materia['nombre']); ?>',
            cantidad: Math.floor(Math.random()*6)+5 // 5‑10 aleatorio
        })
    })
    .then(r=>r.json())
    .then(preguntas=>{
        const cont = document.getElementById('preguntasContainer');
        const correctas = {};
        preguntas.forEach((p,i)=>{
            correctas[i]=p.respuesta_correcta;
            const div = document.createElement('div');
            div.className='mb-3';
            div.innerHTML = `<h5>Pregunta ${i+1}: ${p.pregunta}</h5>`+
                `<div class='form-check'><input class='form-check-input' type='radio' name='respuesta[${i}]' value='A' required> <label class='form-check-label'>A) ${p.opcion_a}</label></div>`+
                `<div class='form-check'><input class='form-check-input' type='radio' name='respuesta[${i}]' value='B'> <label class='form-check-label'>B) ${p.opcion_b}</label></div>`+
                `<div class='form-check'><input class='form-check-input' type='radio' name='respuesta[${i}]' value='C'> <label class='form-check-label'>C) ${p.opcion_c}</label></div>`+
                `<div class='form-check'><input class='form-check-input' type='radio' name='respuesta[${i}]' value='D'> <label class='form-check-label'>D) ${p.opcion_d}</label></div>`;
            cont.appendChild(div);
        });
        // Guardar respuestas correctas ocultas para comparar en backend
        const hidden = document.createElement('input');
        hidden.type='hidden';
        hidden.name='_correcta';
        hidden.value=JSON.stringify(correctas);
        // Pero enviamos como JSON string separado; utilizaremos PHP para decodificar
        // Enviamos como campo separado por name pattern
        const correctInput = document.createElement('input');
        correctInput.type='hidden';
        correctInput.name='correctas_json';
        correctInput.value=JSON.stringify(correctas);
        form.appendChild(correctInput);
    })
    .catch(err=>{
        alert('Error al cargar preguntas: '+err);
    });
}

document.addEventListener('keydown', e=>{
    switch(e.key){
        case 'ArrowUp': case 'w': mover(0,-1); break;
        case 'ArrowDown': case 's': mover(0,1); break;
        case 'ArrowLeft': case 'a': mover(-1,0); break;
        case 'ArrowRight': case 'd': mover(1,0); break;
    }
});

dibujar();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
