# AgendaWeb — P8

Proyecto AgendaWeb con registro de usuarios, inicio de sesión, sesiones y eventos separados por usuario.

## Archivos incluidos

```text
agenda-web/
├── index.php
├── registro.php
├── login.php
├── logout.php
├── registrar.php
├── editar.php
├── borrar.php
├── conexion.ejemplo.php
├── migracion_p8.sql
├── db.sql
├── .gitignore
├── css/
│   └── estilos.css
└── partes/
    └── sesion.php
```

## Archivo que no debe subir a Git

No se incluye `conexion.php` porque contiene las credenciales de la base de datos. El archivo `.gitignore` ya contiene:

```text
conexion.php
```

En el servidor crea `conexion.php` a partir de `conexion.ejemplo.php` y escribe las credenciales reales de DomCloud.

```php
<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mysqli = new mysqli(
    'localhost',
    'USUARIO_DE_DOMCLOUD',
    'CONTRASEÑA_DE_DOMCLOUD',
    'NOMBRE_DE_LA_BASE'
);

$mysqli->set_charset('utf8mb4');
```

## Instalación de la base de datos

En la base de datos de DomCloud ejecuta la migración en este orden:

1. Respaldar la tabla `eventos`.
2. Crear la tabla `usuarios`.
3. Crear una cuenta desde `registro.php`.
4. Consultar el ID del usuario:

```sql
SELECT id, nombre, correo FROM usuarios;
```

5. Agregar la columna y asignar los eventos existentes usando el ID real:

```sql
ALTER TABLE eventos
ADD COLUMN usuario_id INT NULL AFTER id;

UPDATE eventos
SET usuario_id = ID_REAL_DEL_USUARIO
WHERE usuario_id IS NULL;

ALTER TABLE eventos
  MODIFY usuario_id INT NOT NULL,
  ADD CONSTRAINT fk_eventos_usuario
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
  ON DELETE CASCADE;
```

Si `usuario_id` ya existe, no vuelvas a ejecutar el primer `ALTER TABLE`.

## Prueba local

1. Copia `conexion.ejemplo.php` como `conexion.php`.
2. Configura tus credenciales locales.
3. Inicia el servidor:

```bash
php -S localhost:8000
```

4. Abre:

```text
http://localhost:8000/registro.php
```

## Subir a Git

Desde la carpeta del proyecto:

```bash
git init
git add .
git commit -m "P8: autenticacion y eventos por usuario"
git branch -M main
git remote add origin https://github.com/TU_USUARIO/TU_REPOSITORIO.git
git push -u origin main
```

Antes de hacer `git push`, verifica que no exista un archivo `conexion.php` entre los archivos preparados:

```bash
git status
git diff --cached --name-only
```

## Comprobaciones de P8

- Sin sesión, `index.php`, `registrar.php`, `editar.php` y `borrar.php` redirigen a `login.php`.
- La contraseña se guarda con `password_hash()`.
- El login compara con `password_verify()`.
- Cada consulta de eventos filtra por `usuario_id`.
- El resumen conserva las categorías en cero usando el filtro dentro de `LEFT JOIN ... ON`.
- `logout.php` destruye la sesión.
- `conexion.php` no se sube al repositorio.
