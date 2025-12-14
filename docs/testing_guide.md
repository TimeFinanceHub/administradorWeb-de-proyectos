# Guía de Pruebas

Esta guía describe los pasos para probar manualmente las funcionalidades clave de la aplicación y verificar que todo funciona como se espera.

## Prerrequisitos

*   El proyecto debe estar instalado y en funcionamiento.
*   La base de datos debe estar configurada y vacía (o recién reiniciada).
*   Se recomienda realizar las pruebas en un navegador que tenga las herramientas de desarrollador abiertas (F12) para poder ver cualquier error de consola.

---

### Caso de Prueba 1: Registro de Usuario

**Objetivo:** Verificar que un nuevo usuario puede registrarse correctamente.

1.  **Acción:** Ve a la página de inicio.
2.  **Acción:** Rellena el formulario de "Registrarse" con datos válidos.
3.  **Acción:** Haz clic en "Crear Cuenta Nueva".
4.  **Resultado Esperado:** Deberías ver un mensaje de éxito como "Usuario registrado con éxito. Por favor inicia sesión.".
5.  **Acción:** Intenta registrarte de nuevo con el **mismo correo electrónico**.
6.  **Resultado Esperado:** Deberías ver un mensaje de error como "Error: El correo electrónico ya existe.".

---

### Caso de Prueba 2: Inicio y Cierre de Sesión

**Objetivo:** Verificar que un usuario registrado puede iniciar y cerrar sesión.

1.  **Acción:** Usa el formulario de "Iniciar Sesión" con las credenciales del usuario creado en el Caso de Prueba 1.
2.  **Resultado Esperado:** Deberías ser redirigido al dashboard y ver tu nombre de usuario en el encabezado.
3.  **Acción:** Haz clic en el enlace "Cerrar Sesión".
4.  **Resultado Esperado:** Deberías ser redirigido a la página de inicio de sesión/registro.

---

### Caso de Prueba 3: Gestión de Proyectos (Bloques)

**Objetivo:** Verificar las operaciones CRUD (Crear, Leer, Actualizar, Eliminar) de los proyectos.

1.  **Acción:** Inicia sesión.
2.  **Acción (Crear):** Usa el formulario "Nuevo Proyecto Express" para crear dos proyectos: "Proyecto de Marketing" y "Proyecto de Desarrollo".
3.  **Resultado Esperado:** Ambos proyectos deben aparecer en la lista de tu cadena de bloques. El indicador de integridad de la cadena debe mostrar "INTEGRIDAD DE LA CADENA: VERIFICADA".
4.  **Acción (Editar):**
    *   En el "Proyecto de Marketing", haz clic en `[Editar]`.
    *   Cambia el **Alias** a "Campaña 2025".
    *   Cambia el **Estado** a "En Progreso".
    *   Añade las etiquetas `marketing, urgente`.
    *   Haz clic en "Actualizar Metadatos".
5.  **Resultado Esperado:** Deberías ver los cambios reflejados en el dashboard (el nuevo alias, estado y las etiquetas).
6.  **Acción (Filtrar):**
    *   En los filtros, selecciona el estado "En Progreso". Haz clic en "Aplicar Filtros".
    *   Solo deberías ver el proyecto "Campaña 2025".
    *   Limpia los filtros. Ahora selecciona la etiqueta "marketing". Haz clic en "Aplicar Filtros".
    *   Solo deberías ver el proyecto "Campaña 2025".
7.  **Acción (Eliminar):** En el "Proyecto de Desarrollo", haz clic en `[Eliminar]` y confirma.
8.  **Resultado Esperado:** El "Proyecto de Desarrollo" ya no debe aparecer en la lista.

---

### Caso de Prueba 4: Gestión de Tareas

**Objetivo:** Verificar que las tareas se pueden añadir, actualizar y eliminar.

1.  **Acción:** Inicia sesión y ve al proyecto "Campaña 2025" haciendo clic en `[Tareas G013]`.
2.  **Acción (Crear):** Añade dos tareas: "Definir KPIs" y "Diseñar creatividades".
3.  **Resultado Esperado:** Ambas tareas deben aparecer en la lista con el estado "pending".
4.  **Acción (Actualizar):** Cambia el estado de la tarea "Definir KPIs" a "completed" y haz clic en "Actualizar".
5.  **Resultado Esperado:** El estado de la tarea debe cambiar en la lista.
6.  **Acción (Eliminar):** Elimina la tarea "Diseñar creatividades" haciendo clic en el icono de la papelera.
7.  **Resultado Esperado:** La tarea debe desaparecer de la lista.

---

### Caso de Prueba 5: Gestión de Archivos

**Objetivo:** Verificar que se pueden subir y eliminar archivos.

1.  **Acción:** Ve al dashboard. En el proyecto "Campaña 2025", haz clic en `[Media G016]`.
2.  **Acción (Subir):**
    *   Sube un archivo de imagen (PNG).
    *   Sube un archivo de video (MP4).
    *   Intenta subir un archivo no permitido (ej. un `.txt` o `.pdf`).
3.  **Resultado Esperado:**
    *   Los archivos PNG y MP4 deben aparecer en la lista de "Archivos Adjuntos".
    *   Al intentar subir un archivo no permitido, deberías ver un mensaje de error.
4.  **Acción (Eliminar):** Haz clic en `[Eliminar]` en el archivo PNG que subiste.
5.  **Resultado Esperado:** El archivo debe desaparecer de la lista.

---

### Caso de Prueba 6: Reportes

**Objetivo:** Verificar que el dashboard de reportes muestra datos correctos.

1.  **Acción:** Haz clic en `[Reportes]` desde el dashboard.
2.  **Resultado Esperado:** Deberías ver:
    *   **Estadísticas de Proyectos:** 1 proyecto en estado "En Progreso".
    *   **Progreso Global de Tareas:** Deberías ver un total de 1 tarea y 1 completada, con un progreso del 100%.

---

### Caso de Prueba 7: Integridad y Reinicio de la Cadena

**Objetivo:** Verificar las funcionalidades de seguridad y mantenimiento.

1.  **Acción (Simular Corrupción):**
    *   Accede a tu herramienta de base de datos (como phpMyAdmin o DBeaver).
    *   Ve a la tabla `blocks`.
    *   Edita manualmente el campo `data` de cualquier bloque y guarda el cambio.
2.  **Acción:** Vuelve al dashboard de la aplicación y recarga la página.
3.  **Resultado Esperado:** Deberías ver un mensaje de advertencia: "⚠ ALERTA CRÍTICA: DATOS ALTERADOS".
4.  **Acción (Reiniciar Cadena):**
    *   En el dashboard, haz clic en el botón **"Reiniciar Cadena"**.
    *   Confirma la acción.
5.  **Resultado Esperado:**
    *   Serás redirigido al dashboard.
    *   Toda la lista de proyectos debería haber desaparecido.
    *   Debería aparecer un "Bloque Génesis" inicial.
    *   El indicador de integridad debería volver a ser "✓ INTEGRIDAD DE LA CADENA: VERIFICADA".
    *   Tu cuenta de usuario no debe haber sido eliminada.
