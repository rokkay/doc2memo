# Plan: migrar orquestacion de memoria tecnica a Batch

## Objetivo
- Reemplazar el job puente `GenerateTechnicalMemory` por orquestacion con `Bus::batch`.
- Mantener semantica de negocio: continuar aunque fallen secciones.
- Mantener en UI solo el progreso de contenido (secciones completadas), no el porcentaje del batch.

## Alcance
- Actualizar la accion de orquestacion para crear y despachar un batch por corrida.
- Integrar reintentos de calidad dentro del mismo batch cuando exista contexto de batch.
- Ajustar puntos de entrada (Livewire y Controller) para usar el servicio de generacion.
- Persistir `batch_id` en corridas metricas para trazabilidad interna.
- Adaptar pruebas de cola a pruebas de batch.

## Cambios tecnicos
1. `GenerateTechnicalMemoryAction`
   - Crear `Bus::batch` con jobs `GenerateTechnicalMemorySection`.
   - Usar `allowFailures()`.
   - Nombrar batch con referencia de licitacion y corrida.
   - Guardar `batch_id` en `technical_memory_metric_runs`.

2. `GenerateTechnicalMemorySection`
   - Agregar `Batchable`.
   - Cambiar reintento de `self::dispatch(...)` a `batch()->add(...)` cuando haya batch.
   - Mantener fallback a `self::dispatch(...)` fuera de batch (regeneracion individual).

3. Entrypoints
   - `TenderDetail` y `TenderController` usan `TechnicalMemoryGenerationService` en vez del job puente.

4. Datos metricos
   - Nueva migracion para columna nullable `batch_id` en `technical_memory_metric_runs`.
   - Actualizar modelo y action de upsert para soportar el campo.

5. Pruebas
   - Migrar asserts de `Queue::assertPushed` a `Bus::assertBatched` donde corresponda.
   - Conservar verificacion funcional de progreso por contenido.

## Verificacion
- `php artisan test --compact tests/Feature/Jobs/GenerateTechnicalMemoryTest.php`
- `php artisan test --compact tests/Feature/Jobs/GenerateTechnicalMemorySectionTest.php`
- `php artisan test --compact tests/Feature/TechnicalMemoryMetricRunSummaryTest.php`
- `php artisan test --compact tests/Feature/Livewire/Tenders/TenderDetailTest.php`
- `vendor/bin/pint --dirty --format agent`
