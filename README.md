# CMS Estático XML

Sistema de gestión de contenidos (CMS) que genera páginas estáticas HTML a partir de archivos XML. No utiliza bases de datos; toda la información se almacena en archivos XML en el sistema de archivos. Incluye un panel de administración escrito en PHP con autenticación por código OTP enviado al correo electrónico.

## Características Principales

- **Sin base de datos**: Toda la información se almacena en archivos XML ligeros
- **Páginas estáticas**: El sitio público sirve HTML pre-generado para máxima velocidad
- **Panel de administración PHP**: Creación y edición de artículos, categorías y usuarios
- **Autenticación OTP**: Inicio de sesión seguro mediante código de 6 dígitos enviado al email
- **Editor Markdown**: Los artículos se escriben en Markdown y se convierten a HTML
- **Programación de publicaciones**: Los artículos pueden programarse para publicarse automáticamente
- **Sistema de categorías**: Organización de artículos por categorías con paginación
- **Buscador integrado**: Búsqueda en tiempo real con índice optimizado en JSON
- **SEO optimizado**: Meta tags, Open Graph, JSON-LD, URLs amigables
- **URLs limpias**: Sin extensiones .html, .php ni la palabra index
- **Compresión GZIP**: Compresión automática de todos los recursos
- **Caché agresivo**: Headers de caché para recursos estáticos
- **Docker ready**: Despliegue fácil con Docker y Docker Compose
- **Cron automático**: Publicación automática de artículos programados
- **Tiempo de carga**: Indicador de tiempo de carga al pie de cada página

## Requisitos

- Docker y Docker Compose (para despliegue con Docker)
- O alternativamente:
  - Apache 2.4+ con mod_rewrite, mod_headers, mod_deflate, mod_expires
  - PHP 8.0+
  - Extensiones PHP: zip, intl, opcache
  - Acceso a servidor SMTP (Gmail, Yahoo, etc.)
  - Cron o tarea programada

## Instalación con Docker

### 1. Clonar el repositorio

```bash
git clone https://github.com/tu-usuario/cms-estatico-xml.git
cd cms-estatico-xml
```

### 2. Construir y levantar el contenedor

```bash
docker-compose up -d --build
```

### 3. Acceder al CMS

El CMS estará disponible en `http://localhost:8080`.

### 4. Registro inicial del administrador

1. Accede a `http://localhost:8080/admin/registrar`
2. Completa el formulario con:
   - Nombre de usuario
   - Correo electrónico
   - Contraseña (mínimo 6 caracteres)
   - Nombre para mostrar
3. Serás redirigido al inicio de sesión

### 5. Configurar SMTP

1. Inicia sesión como administrador
2. Ve a **Configuración** en el menú lateral
3. Configura los datos SMTP:
   - **Host**: `smtp.gmail.com` (para Gmail)
   - **Puerto**: `587`
   - **Usuario**: tu correo Gmail
   - **Contraseña**: contraseña de aplicación (no la contraseña normal de Gmail)
   - **Encriptación**: `tls`
4. Guarda los cambios

> **Nota para Gmail**: Debes generar una [contraseña de aplicación](https://support.google.com/accounts/answer/185833) en tu cuenta de Google.

## Instalación Manual (sin Docker)

### 1. Copiar archivos

Copia todos los archivos al directorio raíz de tu servidor Apache.

### 2. Instalar dependencias

```bash
composer install
```

### 3. Permisos

```bash
chmod -R 775 data/ cache/ static/
chown -R www-data:www-data data/ cache/ static/
```

### 4. Configurar Apache

Asegúrate de que estos módulos estén habilitados:

```bash
a2enmod rewrite headers deflate expires
```

### 5. Configurar cron

Agrega esta línea al crontab del servidor:

```bash
* * * * * cd /ruta/al/cms && php cron/publish-scheduled.php
```

## Estructura del Proyecto

```
cms-estatico-xml/
├── .htaccess                # URLs limpias, gzip, caché, seguridad
├── Dockerfile               # Imagen Docker con Apache + PHP
├── docker-compose.yml       # Orquestación del contenedor
├── docker-entrypoint.sh     # Script de inicio del contenedor
├── composer.json            # Dependencias PHP (PHPMailer)
├── .gitignore
├── README.md
│
├── includes/                # Archivos core PHP
│   ├── config.php           # Configuración y constantes
│   ├── functions.php        # Funciones de utilidad
│   ├── auth.php             # Autenticación OTP
│   ├── mailer.php           # Envío de correos SMTP
│   ├── markdown.php         # Parser Markdown a HTML
│   ├── xml-articles.php     # CRUD de artículos XML
│   ├── xml-categories.php   # CRUD de categorías XML
│   ├── xml-users.php        # CRUD de usuarios XML
│   └── generator.php        # Generador de HTML estático
│
├── data/                    # Almacenamiento XML (no accesible públicamente)
│   ├── articles/            # Artículos (un XML por artículo)
│   ├── categories/          # Categorías (un XML por categoría)
│   ├── users/               # Usuarios (un XML por usuario)
│   ├── index/               # Índice de búsqueda JSON
│   └── settings.xml         # Configuración del sitio
│
├── cache/                   # Archivos temporales
├── static/                  # HTML estático generado
│
├── admin/                   # Panel de administración
│   ├── index.php            # Dashboard
│   ├── setup.php            # Registro inicial de admin
│   ├── login.php            # Inicio de sesión OTP
│   ├── verify.php           # Verificación de código OTP
│   ├── logout.php           # Cerrar sesión
│   ├── articles.php         # Lista de artículos
│   ├── article-edit.php     # Crear/editar artículo
│   ├── categories.php       # Lista de categorías
│   ├── category-edit.php    # Crear/editar categoría
│   ├── users.php            # Lista de usuarios (admin)
│   ├── user-edit.php        # Crear/editar usuario (admin)
│   ├── settings.php         # Configuración del sitio (admin)
│   ├── generate.php         # Regenerar páginas estáticas
│   ├── api/                 # Endpoints API
│   │   ├── create-category.php
│   │   └── preview.php
│   └── assets/              # CSS y JS del admin
│
├── templates/               # Plantillas HTML
│   ├── home.php             # Portada
│   ├── article.php          # Página de artículo
│   ├── category.php         # Listado de categoría
│   ├── search.php           # Buscador
│   ├── 404.php              # Página no encontrada
│   └── partials/            # Componentes comunes
│       ├── head.php
│       ├── header.php
│       └── footer.php
│
├── assets/                  # Recursos públicos
│   ├── css/style.css
│   └── js/
│
└── cron/
    └── publish-scheduled.php  # Tarea programada
```

## URLs del Sistema

### Sitio Público

| Ruta | Descripción |
|------|-------------|
| `/` | Portada (últimos artículos) |
| `/page/2` | Portada - Página 2 |
| `/titulo-del-articulo` | Página de artículo individual |
| `/categoria/nombre` | Artículos de una categoría |
| `/categoria/nombre/page/2` | Categoría - Página 2 |
| `/buscar` | Página de búsqueda |
| `/buscar?q=termino` | Búsqueda con query |

### Panel de Administración

| Ruta | Descripción |
|------|-------------|
| `/admin` | Dashboard |
| `/admin/registrar` | Registro inicial de admin |
| `/admin/login` | Inicio de sesión |
| `/admin/salir` | Cerrar sesión |
| `/admin/articulos` | Lista de artículos |
| `/admin/nuevo-articulo` | Crear artículo |
| `/admin/articulo/slug` | Editar artículo |
| `/admin/categorias` | Lista de categorías |
| `/admin/nueva-categoria` | Crear categoría |
| `/admin/categoria/slug` | Editar categoría |
| `/admin/usuarios` | Lista de usuarios (admin) |
| `/admin/nuevo-usuario` | Crear usuario (admin) |
| `/admin/usuario/nombre` | Editar usuario (admin) |
| `/admin/configuracion` | Configuración del sitio (admin) |
| `/admin/regenerar` | Regenerar páginas estáticas |

## Markdown Soportado

El editor de artículos soporta la siguiente sintaxis Markdown:

### Encabezados

```markdown
# Encabezado 1
## Encabezado 2
### Encabezado 3
#### Encabezado 4
##### Encabezado 5
###### Encabezado 6
```

### Énfasis

```markdown
*texto en itálica* o _texto en itálica_
**texto en negrita** o __texto en negrita__
***texto en negrita e itálica***
~~texto tachado~~
```

### Enlaces e Imágenes

```markdown
[Texto del enlace](https://ejemplo.com)
[Enlace con título](https://ejemplo.com "Título del enlace")
![Texto alternativo](ruta/imagen.png)
![Imagen con título](ruta/imagen.png "Título de la imagen")
```

### Listas

```markdown
- Elemento 1
- Elemento 2
- Elemento 3

1. Primer elemento
2. Segundo elemento
3. Tercer elemento
```

### Código

```markdown
Código inline: `variable = True`

Bloque de código:
```python
def saludar(nombre):
    return f"Hola, {nombre}!"
```
```

### Citas

```markdown
> Esto es una cita
> Puede ser de varias líneas
```

### Línea Horizontal

```markdown
---
```

### Párrafos

```markdown
Este es un párrafo.

Este es otro párrafo separado por una línea en blanco.
```

## Roles de Usuario

| Rol | Permisos |
|-----|----------|
| **Admin** | Gestión completa: artículos (propios y de todos), categorías, usuarios, configuración, regenerar páginas |
| **Editor** | Crear y editar sus propios artículos. No puede gestionar usuarios ni configuración |

## Rendimiento

El CMS está diseñado para ser extremadamente rápido:

- **HTML pre-generado**: Las páginas estáticas se sirven directamente sin procesamiento PHP
- **Compresión GZIP**: Todos los recursos se comprimen automáticamente
- **Caché de navegador**: Headers Cache-Control y Expires para recursos estáticos
- **OPcache**: El código PHP se cachea en memoria
- **Keep-Alive**: Conexiones persistentes HTTP
- **Sin consultas a BD**: No hay overhead de base de datos
- **Tiempo de carga objetivo**: Menos de 0.5 segundos

## Configuración SMTP

### Gmail

| Campo | Valor |
|-------|-------|
| Host | `smtp.gmail.com` |
| Puerto | `587` |
| Usuario | `tu-correo@gmail.com` |
| Contraseña | Contraseña de aplicación de Google |
| Encriptación | `tls` |

### Yahoo

| Campo | Valor |
|-------|-------|
| Host | `smtp.mail.yahoo.com` |
| Puerto | `587` |
| Usuario | `tu-correo@yahoo.com` |
| Contraseña | Contraseña de aplicación de Yahoo |
| Encriptación | `tls` |

### Outlook/Hotmail

| Campo | Valor |
|-------|-------|
| Host | `smtp-mail.outlook.com` |
| Puerto | `587` |
| Usuario | `tu-correo@outlook.com` |
| Contraseña | Contraseña de aplicación |
| Encriptación | `starttls` |

## Comandos Docker Útiles

```bash
# Construir y levantar
docker-compose up -d --build

# Ver logs
docker-compose logs -f

# Detener
docker-compose down

# Reiniciar
docker-compose restart

# Acceder al contenedor
docker-compose exec cms bash

# Verificar permisos
docker-compose exec cms ls -la data/

# Regenerar páginas estáticas manualmente
docker-compose exec cms php /var/www/html/cron/publish-scheduled.php
```

## Flujo de Trabajo

1. **Registro**: El admin accede a `/admin/registrar` y crea su cuenta
2. **Configuración**: Configura el sitio y el SMTP en `/admin/configuracion`
3. **Categorías**: Crea categorías en `/admin/categorias`
4. **Artículos**: Crea artículos en Markdown en `/admin/nuevo-articulo`
5. **Publicación**: Los artículos se publican inmediatamente o se programan
6. **Generación**: Al guardar, las páginas estáticas se regeneran automáticamente
7. **Cron**: Cada minuto se verifican artículos programados para publicar
8. **Usuarios**: El admin puede crear más usuarios editores desde `/admin/usuarios`

## Seguridad

- Archivos XML protegidos contra acceso directo
- Archivos de configuración no accesibles públicamente
- Contraseñas hasheadas con `password_hash()` (bcrypt)
- Sesiones seguras con cookies httponly
- Headers de seguridad (X-Frame-Options, X-Content-Type-Options, X-XSS-Protection)
- Los datos se guardan fuera del directorio público
- Timeout de sesión configurable

## Licencia

MIT
