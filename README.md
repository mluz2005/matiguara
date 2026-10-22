# MatiGuará — Aplicación educativa gamificada con módulo bilingüe (español–guaraní)

**Pesallaccia Cardozo, María Luz — FACAT-UNAE Encarnación, 2026**
*Proyecto de Grado — Licenciatura en Análisis de Sistemas Informáticos*

Aplicación web para el aprendizaje de matemática en el primer ciclo de la Educación Escolar
Básica (6 a 9 años): tres juegos con retroalimentación bilingüe simultánea, ajuste adaptativo
de nivel, logros y rachas, paneles para docentes, familias y administración, y diagnóstico
pedagógico con IA bajo demanda del docente.

---

## Stack tecnológico (versiones reales del proyecto)

| Componente | Versión / detalle |
|---|---|
| Backend | Laravel **9.52** (PHP **8.0**, `composer.json`: `^8.0.2`) |
| Base de datos | MariaDB **10.4** (XAMPP; compatible con MySQL 5.7+). Base: `matiguara_db` |
| Frontend | Blade + Bootstrap 5.3 y Bootstrap Icons por CDN (no se compilan assets) |
| Entorno de desarrollo | XAMPP en Windows (Apache + MariaDB), `C:\xampp\htdocs\app-educativa` |
| Pruebas | PHPUnit 9 sobre una base separada `matiguara_test` |
| IA | API Claude (Anthropic), solo bajo demanda explícita del docente (RN-05) |
| Cola de tareas | `QUEUE_CONNECTION=sync` por defecto |

> Importante: el código debe seguir siendo compatible con **PHP 8.0**. No usar `readonly`,
> `enum`, `new` en valores por defecto ni la sintaxis `funcion(...)` de PHP 8.1.

---

## Arquitectura en capas

```
Rutas (routes/web.php) + Middleware (es.docente, es.padre, es.admin, EstudianteActivo)
        ↓
Controllers por dominio (app/Http/Controllers/Auth, Dashboard)
        ↓
Services (app/Services)         ← lógica de negocio
        ↓
Repositories (app/Repositories) ← TODO el acceso a datos
        ↓
Models Eloquent (app/Models)    ← mapeo de tablas y relaciones
        ↓
MariaDB
```

Regla de la arquitectura: los controladores, servicios, helpers (`app/Support`) y jobs **no
consultan los modelos directamente**; piden los datos a un repositorio, que se recibe por
inyección en el constructor (autowiring del contenedor de Laravel, sin interfaces ni
proveedores adicionales). Los modelos conservan sus relaciones y *scopes*.

| Repositorio | Responsabilidad |
|---|---|
| `SesionRepository` | Sesiones de juego, movimientos, resúmenes de rendimiento |
| `EjercicioRepository` | Ejercicios y niveles (siguiente, anterior, adyacente) |
| `EstudianteRepository` | Cuentas, progreso, rachas, búsquedas por código y correo |
| `MemoriaRepository` | Tarjetas y movimientos de «Encuentra el par» |
| `LogroRepository` | Catálogo y logros obtenidos |
| `JuegoRepository` | Juegos activos, niveles por grado, configuración |
| `MensajeRetroRepository` | Mensajes de retroalimentación bilingües |
| `DiagnosticoRepository` | Diagnósticos pedagógicos generados |
| `DocenteRepository` | Clase del docente: matrículas, alumnos propios, juegos habilitados, altas y bajas |
| `UsuarioRepository` | Cuentas de usuario (correo de personal, cuentas de familia) |
| `PadreRepository` | Vínculos familia–estudiante |
| `InstitucionRepository` | Instituciones y su configuración operativa |

Única excepción deliberada: `app/Providers/EstudianteProvider.php` (proveedor de autenticación
de estudiantes de Laravel), que es el adaptador del framework hacia la base de datos para el login.

---

## Instalación en XAMPP (Windows)

1. Instalar XAMPP con PHP 8.0 y MariaDB 10.4, y Composer 2.
2. Copiar el proyecto en `C:\xampp\htdocs\app-educativa` y, dentro de esa carpeta:
   ```bash
   composer install
   copy .env.example .env
   php artisan key:generate
   ```
3. Iniciar **Apache y MySQL** desde el panel de XAMPP (si MySQL está apagado, el login falla con
   `SQLSTATE[HY000] [2002]`).
4. Crear la base vacía en phpMyAdmin (cotejamiento `utf8mb4_unicode_ci`):
   ```sql
   CREATE DATABASE matiguara_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
5. Editar `.env`:
   ```env
   APP_NAME="MatiGuará"
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost/app-educativa/public

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=matiguara_db
   DB_USERNAME=root
   DB_PASSWORD=

   QUEUE_CONNECTION=sync
   SESSION_DRIVER=file

   # Diagnóstico con IA (RF-10). Sin clave, el diagnóstico se genera con datos locales.
   CLAUDE_API_KEY=
   CLAUDE_MODEL=claude-sonnet-4-6
   CLAUDE_MAX_TOKENS=1000
   ```
6. Crear las tablas y cargar los datos iniciales:
   ```bash
   php artisan migrate --seed
   php artisan db:seed --class=MemoriaJuegoSeeder   # tercer juego: «Encuentra el par»
   php artisan db:seed --class=ContenidoGrados23Seeder   # contenido de 2.° y 3.er grado (ejecutar al final)
   ```
7. Abrir `http://localhost/app-educativa/public` (o `php artisan serve` → `http://localhost:8000`).

### Limitación conocida del contenido de los juegos en instalaciones nuevas
Varias migraciones ajustan el **contenido** de los juegos ya cargados (ampliación de «Suma de
Frutas», «Flash Matemático» a 10 niveles, tiempos, «Encuentra el par» v2). Se ejecutan *antes* de
los sembradores y, si el juego todavía no existe, no hacen nada. Por eso una instalación nueva
queda con el contenido inicial de los sembradores y no con el de la base `matiguara_db` del
piloto. Para reproducir exactamente el contenido del piloto hay que restaurar un respaldo
(`mysqldump`) de `matiguara_db`. Consolidar ese contenido en los sembradores es trabajo pendiente.

---

## Pruebas automatizadas

Las pruebas viven en `tests/Feature/Repositories` y cubren: cada repositorio, los servicios
(puntaje, regla adaptativa 3/2, logros, retroalimentación, memoria), el desbloqueo de niveles,
la baja automática de egresados y el comportamiento HTTP de los paneles de docente,
administración y familias y del acceso de familias.

**SEGURIDAD:** `RefreshDatabase` borra y recrea todas las tablas de la base configurada. Por eso
las pruebas usan una base **aparte** (`phpunit.xml` fija `DB_DATABASE=matiguara_test`) y se
niegan a correr si el nombre de la base no termina en `_test`.

```sql
CREATE DATABASE matiguara_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
```bash
php artisan test --filter=Repositories
```

---

## Credenciales de prueba (solo desarrollo; las crean los sembradores)

| Rol | Correo | Contraseña |
|---|---|---|
| Administradora | admin@unae.edu.py | Admin1234! |
| Docente 1 (1.er grado) | docente1@unae.edu.py | Docente123! |
| Docente 2 (2.º grado) | docente2@unae.edu.py | Docente123! |
| Familia | padre@correo.com | Padre1234! |

| Estudiante | Código de acceso | PIN |
|---|---|---|
| Ana López | SAN-1A-001 | 1234 |
| Juan Martínez | SAN-1A-002 | 5678 |
| Sofía Torres | SAN-1A-003 | 9012 |
| Diego Ramírez | SAN-2B-001 | 1111 |
| Lucía Benítez | SAN-2B-002 | 2222 |

Las familias también pueden entrar por «Acceso para familias» (`/familias`) con el **correo del
tutor** (cargado por la docente en *Cuentas*) y el **código del niño/a**.

---

## Estructura principal

```
app-educativa/
├── app/
│   ├── Console/Kernel.php            ← tarea diaria de baja de egresados (RF-18)
│   ├── Http/Controllers/
│   │   ├── Auth/                     ← login adultos, estudiantes y familias
│   │   └── Dashboard/                ← Docente, Estudiante, Memoria, Padre, Admin, DocenteNiveles
│   ├── Http/Middleware/              ← EsDocente, EsPadre, EsAdmin, EstudianteActivo
│   ├── Jobs/EgresadoCleanupJob.php   ← desactivación de cuentas de egresados
│   ├── Models/                       ← modelos Eloquent
│   ├── Providers/EstudianteProvider.php ← proveedor de autenticación de estudiantes
│   ├── Repositories/                 ← capa de acceso a datos (12 repositorios)
│   ├── Services/                     ← SesionService, SistemaAdaptativoService, LogroService,
│   │                                    MemoriaService, RetroalimentacionService, DiagnosticoIAService,
│   │                                    MatriculaService (cierre de año, egreso y grados)
│   └── Support/                      ← DesbloqueoNiveles, ayudas y utilidades
├── database/migrations/              ← esquema y ajustes de contenido
├── database/seeders/                 ← datos iniciales (DatabaseSeeder + MemoriaJuegoSeeder + ContenidoGrados23Seeder)
├── resources/views/                  ← admin, docente, estudiante, padre, auth, layouts
├── routes/web.php
└── tests/Feature/Repositories/       ← pruebas automatizadas
```

---

## Cierre del año lectivo (RF-18, RN-10) y grados anteriores (RF-17)

- En **Cuentas**, el docente elige un alumno y pulsa *Cerrar año*: 1.° y 2.° pasan al grado siguiente
  (misma institución y docente; solo si ya hay niveles de juego cargados para ese grado) y 3.° egresa.
- Al egresar se calcula `estudiantes.fecha_baja_programada` = fecha de cierre + *meses de conservación* de la
  institución (4 por defecto, configurable por la administradora). La tarea diaria (`egresados-cleanup`, 02:00
  America/Asuncion) desactiva la cuenta cuando esa fecha llega; el historial se conserva. Para que la tarea corra
  hay que programar `php artisan schedule:run` cada minuto (Programador de tareas de Windows o cron).
- El egresado no puede jugar, pero ve su progreso hasta la baja.
- El estudiante ve el progreso de su grado activo; los grados anteriores solo si la institución activó
  *permitir acceso a grados anteriores*. El docente ve todo el historial del alumno.
- Contenido por grado: DatabaseSeeder y MemoriaJuegoSeeder cargan solo 1.° grado. `ContenidoGrados23Seeder` (o la migración `2026_10_21_000001`, en una base ya existente) agrega 2.° y 3.er grado a Suma de Frutas (8 niveles por grado, hasta 100 y hasta 1000) y a Encuentra el par; Flash Matemático ya trae los tres grados mediante una migración de datos. Mientras un grado no tenga niveles, el pase a ese grado se rechaza.

---

## Estado y trabajo pendiente
- Prototipo funcional con tres juegos (Suma de Frutas, Flash Matemático, Encuentra el par).
- Pendiente: validación lingüística del guaraní por una persona hablante, pruebas con usuarios
  (SUS), prueba piloto y consolidación del contenido de los juegos en los sembradores.

*TFG — Pesallaccia Cardozo, M. L. | FACAT-UNAE Encarnación | 2026*
