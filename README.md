# Aplicación Educativa Gamificada con Módulo Bilingüe
**Pesallaccia Cardozo, María Luz — FACAT-UNAE Encarnación 2026**
*Proyecto de Grado — Licenciatura en Análisis de Sistemas Informáticos*

---

## Stack Tecnológico
- **Backend**: Laravel 11 (PHP 8.2+)
- **Base de datos**: MySQL 8.0
- **Frontend**: Bootstrap 5 (Blade Templates)
- **Cola de tareas**: Laravel Queue (driver: database)
- **IA**: API Claude (Anthropic) — solo bajo demanda explícita del docente

---

## Requisitos del Sistema
| Herramienta | Versión mínima |
|-------------|---------------|
| PHP         | 8.2           |
| Composer    | 2.x           |
| MySQL       | 8.0           |
| Node.js     | 18.x (para compilar assets) |
| npm         | 9.x           |

---

## Instalación paso a paso

### 1. Clonar/copiar el proyecto
```bash
# Si estás creando el proyecto desde cero con Laravel:
composer create-project laravel/laravel app-educativa
cd app-educativa

# Luego copiar las migraciones, modelos, jobs y seeders de este repositorio
cp -r database/migrations/* ./database/migrations/
cp -r app/Models/* ./app/Models/
cp -r app/Jobs/* ./app/Jobs/
cp -r app/Http/Middleware/* ./app/Http/Middleware/
cp -r database/seeders/* ./database/seeders/
```

### 2. Configurar el archivo .env
```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env`:
```env
APP_NAME="App Educativa Bilingüe"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=app_educativa
DB_USERNAME=root
DB_PASSWORD=tu_password

# Cola de tareas (EgresadoCleanupJob RF-18)
QUEUE_CONNECTION=database

# API Claude (Anthropic) — RF-10
CLAUDE_API_KEY=sk-ant-xxxxx
CLAUDE_MODEL=claude-sonnet-4-6
CLAUDE_MAX_TOKENS=1000

# Sesiones
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

### 3. Crear la base de datos
```bash
mysql -u root -p -e "CREATE DATABASE app_educativa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 4. Ejecutar migraciones
```bash
# Crea las 21 tablas en el orden correcto (respeta FKs)
php artisan migrate

# Si querés partir de cero (desarrollo):
php artisan migrate:fresh
```

### 5. Cargar datos iniciales (seeders)
```bash
php artisan db:seed

# O todo junto:
php artisan migrate:fresh --seed
```

Esto crea:
- 6 tipos de mecánica matemática
- 13 mensajes de retroalimentación bilingüe
- 9 logros en el catálogo
- 2 instituciones (con config_institucion por defecto)
- 1 admin, 2 docentes, 1 padre
- 5 estudiantes con código+PIN
- Asignaciones y matrículas
- 1 juego con 5 ejercicios de suma simple

### 6. Registrar middleware en Kernel
En `app/Http/Kernel.php`, agregar en `$middlewareAliases`:
```php
'es.docente' => \App\Http\Middleware\EsDocente::class,
'es.padre'   => \App\Http\Middleware\EsPadre::class,
'es.admin'   => \App\Http\Middleware\EsAdmin::class,
```

### 7. Configurar el scheduler (EgresadoCleanupJob)
En `app/Console/Kernel.php`:
```php
protected function schedule(Schedule $schedule): void
{
    // RF-18: Verificación diaria de cuentas de egresados a desactivar
    $schedule->call(function () {
        Estudiante::bajaVencida()->each(function (Estudiante $est) {
            EgresadoCleanupJob::dispatch($est);
        });
    })->daily()->description('Cleanup cuentas egresados');
}
```

En cron del servidor (producción):
```cron
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### 8. Crear tabla de cola de tareas
```bash
php artisan queue:table
php artisan migrate
```

### 9. Levantar el servidor de desarrollo
```bash
php artisan serve
# Disponible en: http://localhost:8000

# En otra terminal, procesar la cola:
php artisan queue:work
```

---

## Credenciales de prueba (solo desarrollo)
| Rol           | Email                   | Contraseña    |
|---------------|-------------------------|---------------|
| Admin         | admin@unae.edu.py       | Admin1234!    |
| Docente 1     | docente1@unae.edu.py    | Docente123!   |
| Docente 2     | docente2@unae.edu.py    | Docente123!   |
| Padre         | padre@correo.com        | Padre1234!    |

| Estudiante    | Código acceso  | PIN  |
|---------------|----------------|------|
| Ana López     | SAN-1A-001     | 1234 |
| Juan Martínez | SAN-1A-002     | 5678 |
| Sofía Torres  | SAN-1A-003     | 9012 |
| Diego Ramírez | SAN-2B-001     | 1111 |
| Lucía Benítez | SAN-2B-002     | 2222 |

---

## Estructura de archivos generados
```
app-educativa/
├── app/
│   ├── Http/Middleware/
│   │   ├── EsDocente.php        ← perfil docente (RF-01)
│   │   ├── EsPadre.php          ← perfil padre (RN-04)
│   │   └── EsAdmin.php          ← perfil admin
│   ├── Jobs/
│   │   └── EgresadoCleanupJob.php  ← ciclo de vida egresados (RF-18)
│   └── Models/
│       ├── Institucion.php      ← Tabla 1/21
│       ├── Usuario.php          ← Tabla 2/21
│       ├── Estudiante.php       ← Tabla 3/21 (código+PIN, UUID)
│       ├── Docente.php          ← Tabla 4/21
│       ├── Padre.php            ← Tabla 5/21
│       ├── AsignacionDocente.php← Tabla 6/21
│       ├── Matricula.php        ← Tabla 7/21
│       ├── TipoMecanica.php     ← Tabla 8/21
│       ├── Juego.php            ← Tabla 9/21
│       ├── Nivel.php            ← Tabla 10/21
│       ├── ContenidoNivel.php   ← Tabla 11/21
│       ├── MensajeRetro.php     ← Tabla 12/21
│       ├── ProgresoEstudiante.php ← Tabla 13/21
│       ├── SesionJuego.php      ← Tabla 14/21
│       ├── MovimientoSesion.php ← Tabla 15/21
│       ├── CatalogoLogro.php    ← Tabla 16/21
│       ├── LogroObtenido.php    ← Tabla 17/21
│       ├── Racha.php            ← Tabla 18/21
│       ├── ConfigDocenteJuego.php ← Tabla 19/21
│       ├── Diagnostico.php      ← Tabla 20/21 ★ nueva v1.2
│       └── ConfigInstitucion.php← Tabla 21/21 ★ nueva v1.2
├── database/
│   ├── migrations/              ← 22 migraciones en orden FK
│   └── seeders/                 ← 7 seeders con datos de prueba
└── README.md                    ← este archivo
```

---

## Próximos pasos (Incremento I3 — Octubre 2026)
- [ ] Sistema de autenticación con 3 guards (adultos / estudiante)
- [ ] Controllers: AuthController, EjercicioController
- [ ] Services: SistemaAdaptativoService, RetroalimentacionService
- [ ] Vistas Blade: login adulto, login estudiante (código+PIN)
- [ ] Motor de ejercicios: mostrar, registrar, evaluar respuesta

---

*TFG — Pesallaccia Cardozo, M. L. | FACAT-UNAE Encarnación | Septiembre 2026*
