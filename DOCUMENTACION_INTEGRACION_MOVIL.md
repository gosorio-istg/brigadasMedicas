# Integración con BrigadaSalud Android

La documentación consolidada de arquitectura, módulos, ejecución, Firebase, pruebas y diagnóstico está en:

```text
..\BrigadaSalud\docs\DOCUMENTACION_FUNCIONAMIENTO.md
```

Comandos rápidos del backend:

```powershell
php artisan migrate --seed
php artisan test
php artisan schedule:work
php artisan sync:firestore --limit=10
```

MySQL es la fuente de verdad. La app consume `/api/v1`; Firestore solo replica estados no identificatorios de `turnos_realtime` mediante `sync_outbox`.
