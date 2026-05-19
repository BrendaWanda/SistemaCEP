* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:Arial,sans-serif; font-size:10px; color:#000; padding:14px; }
.header-rep {
    display:flex; justify-content:space-between;
    border-bottom:2px solid #1e3a5f; padding-bottom:8px; margin-bottom:10px;
}
.empresa { font-size:12px; font-weight:700; text-transform:uppercase; }
.sub-emp  { font-size:9px; color:#555; margin-top:2px; }
.titulo-reporte {
    font-size:14px; font-weight:700; text-align:center;
    text-transform:uppercase; margin-bottom:2px;
}
.subtitulo { font-size:10px; text-align:center; color:#555; margin-bottom:10px; }
.resumen-grid {
    display:grid; grid-template-columns:repeat(4,1fr);
    gap:8px; margin-bottom:12px;
}
.resumen-item {
    border:1px solid #e2e8f0; border-radius:4px;
    padding:8px; text-align:center;
}
.resumen-val { font-size:20px; font-weight:900; color:#1e3a5f; }
.resumen-lbl { font-size:8px; color:#666; text-transform:uppercase; margin-top:2px; }
.tabla-reporte { width:100%; border-collapse:collapse; font-size:9px; margin-bottom:12px; }
.tabla-reporte th {
    background:#1e3a5f; color:#fff; padding:4px 5px;
    text-align:left; font-size:9px;
}
.tabla-reporte td { padding:3px 5px; border-bottom:1px solid #e2e8f0; }
.tabla-reporte tr:nth-child(even) { background:#f8fafc; }
.pie-rep {
    margin-top:16px; text-align:center; font-size:8px;
    color:#888; border-top:1px solid #ccc; padding-top:6px;
}
.no-print { display:block; }
@media print { .no-print { display:none !important; } body { padding:8px; } }