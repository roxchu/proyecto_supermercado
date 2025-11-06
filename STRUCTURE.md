# 📁 Estructura del Proyecto Supermercado

## 🎯 Nueva Organización

Este documento describe la estructura reorganizada del proyecto para mayor claridad y mantenibilidad.

```
proyecto_supermercado/
├── 📁 assets/                    # Recursos estáticos
│   ├── 📁 css/                   # Hojas de estilo
│   │   ├── main.css              # Estilos principales (antes styles.css)
│   │   └── mostrar-producto.css  # Estilos específicos de producto
│   └── 📁 js/                    # JavaScript
│       ├── 📁 components/        # Componentes modulares
│       │   ├── carrito-events.js
│       │   ├── carrito-ui.js
│       │   ├── carrito.js
│       │   ├── carrusel.js
│       │   ├── catalogo.js
│       │   ├── category-filter.js
│       │   └── menu-lateral.js
│       ├── 📁 modules/           # Módulos de funcionalidad
│       │   ├── auth.js
│       │   ├── carrito-api.js
│       │   ├── gestion-roles.js
│       │   └── products-loader.js
│       ├── 📁 legacy/            # Scripts antiguos (compatibilidad)
│       │   ├── script.js
│       │   └── catalogo.js
│       ├── app.js                # Aplicación principal modular
│       └── carrito.js            # Script de carrito standalone
│
├── 📁 src/                       # Código fuente PHP
│   ├── 📁 api/                   # APIs y endpoints
│   │   ├── 📁 carrito/           # API del carrito
│   │   │   ├── agregar_carrito.php
│   │   │   ├── eliminar_item.php
│   │   │   └── obtener_carrito.php
│   │   ├── 📁 direcciones/       # API de direcciones
│   │   │   ├── direcciones.php
│   │   │   └── guardar_direccion.php
│   │   └── 📁 productos/         # API de productos
│   │       ├── productos.php
│   │       └── agregar_resena.php
│   ├── 📁 auth/                  # Sistema de autenticación
│   │   ├── check_session.php
│   │   ├── control_acceso.php
│   │   ├── login.php
│   │   ├── logout.php
│   │   ├── registro.php
│   │   ├── sin_permiso.php
│   │   └── verificar_rol.php
│   ├── 📁 admin/                 # Panel de administración
│   │   ├── admin_actions.php
│   │   ├── dashboard_admin.css
│   │   ├── dashboard_admin.php
│   │   ├── dashboard_empleado.css
│   │   ├── dashboard_empleado.php
│   │   └── empleados_actioons.php
│   ├── 📁 components/            # Componentes reutilizables
│   │   ├── header.php
│   │   └── footer.php
│   └── 📁 pages/                 # Páginas del sitio
│       └── mostrar.php
│
├── 📁 config/                    # Configuración
│   └── database.php             # Configuración de base de datos
│
├── 📁 database/                  # Base de datos
│   └── supermercado.sql         # Script de la base de datos
│
├── 📁 public/                    # Archivos públicos
│   └── index.html               # Página principal
│
├── 📁 .git/                      # Control de versiones Git
├── .htaccess                     # Configuración del servidor
├── index.php                    # Router principal
└── README.md                     # Documentación
```

## 🚀 Características de la Nueva Estructura

### ✅ **Beneficios de la Reorganización**

1. **📦 Separación clara de responsabilidades**
   - `assets/` → Recursos estáticos (CSS, JS)
   - `src/` → Código fuente PHP
   - `config/` → Configuración
   - `public/` → Archivos públicos

2. **🎯 Organización por funcionalidad**
   - APIs agrupadas por dominio (carrito, productos, direcciones)
   - Autenticación centralizada
   - Componentes reutilizables separados

3. **🔧 Sistema modular de JavaScript**
   - ES6 modules con import/export
   - Separación entre legacy y modular
   - Componentes independientes

4. **🛣️ Router centralizado**
   - URLs limpias con `.htaccess`
   - Manejo centralizado de rutas
   - Compatibilidad con estructura anterior

### 🎨 **Rutas Principales**

| URL | Archivo | Descripción |
|-----|---------|-------------|
| `/` | `public/index.html` | Página principal |
| `/mostrar.php` | `src/pages/mostrar.php` | Detalle de producto |
| `/productos.php` | `src/api/productos/productos.php` | API de productos |
| `/login.php` | `src/auth/login.php` | Autenticación |
| `/dashboard_admin.php` | `src/admin/dashboard_admin.php` | Panel admin |

### 📋 **Archivos de Configuración**

- **`.htaccess`** → Reescritura de URLs y configuración del servidor
- **`index.php`** → Router principal que maneja todas las rutas
- **`config/database.php`** → Configuración centralizada de la base de datos

### 🔄 **Compatibilidad**

✅ **Mantiene compatibilidad total** con:
- URLs existentes
- Referencias JavaScript
- Llamadas AJAX
- Funcionalidad del carrito
- Sistema de autenticación

### 🛠️ **Próximas Mejoras Sugeridas**

1. **Autoloader PSR-4** para clases PHP
2. **Gestión de dependencias** con Composer
3. **Variables de entorno** (.env)
4. **Cache de assets** con versionado
5. **Minificación** de CSS/JS para producción

---

## 🎉 **¡Estructura Completamente Reorganizada!**

El proyecto ahora tiene una estructura profesional, escalable y fácil de mantener. Todos los archivos están en su lugar correcto y las rutas funcionan perfectamente.