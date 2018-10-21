<ul class="nav nav-pills float-right">
    <li class="nav-item">
        <a href="<?=$_base?>/dte/dte_compras/ver/<?=$periodo?>" title="Ir al libro de compras (IEC) del período <?=$periodo?>" class="nav-link">
            IEC <?=$periodo?>
        </a>
    </li>
</ul>
<div class="page-header"><h1>Asignación tipo de transacciones del período <?=$periodo?></h1></div>
<p>Aquí puede hacer una búsqueda de los documentos emitidos y realizar la asignación del tipo de transacción.</p>
<?php
$f = new \sowerphp\general\View_Helper_Form();
echo $f->begin(['onsubmit'=>'Form.check()']);
echo $f->input([
    'name' => 'emisor',
    'label' => 'RUT emisor',
    'check' => 'rut',
]);
echo $f->end('Buscar documentos');

if (!empty($documentos)) {
    echo '<hr/>',"\n";
    foreach ($documentos as &$d) {
        $acciones = '<a href="'.$_base.'/dte/dte_intercambios/ver/'.$d['intercambio'].'" title="Ver detalles del intercambio" class="btn btn-primary'.(!$d['intercambio']?' disabled':'').'"><i class="fa fa-search"></i></a>';
        $acciones .= ' <a href="'.$_base.'/dte/dte_intercambios/pdf/'.$d['intercambio'].'/0/'.$d['emisor'].'/'.$d['dte'].'/'.$d['folio'].'" title="Descargar PDF del documento" class="btn btn-primary'.(!$d['intercambio']?' disabled':'').'"><i class="far fa-file-pdf"></i></a>';
        $acciones .= ' <a href="'.$_base.'/dte/dte_recibidos/modificar/'.$d['emisor'].'/'.$d['dte'].'/'.$d['folio'].'" title="Modificar documento" class="btn btn-primary"><i class="fa fa-edit"></i></a>';
        $d['fecha'] = \sowerphp\general\Utility_Date::format($d['fecha']);
        $d[] = $acciones;
        $d['total'] = num($d['total']);
        unset($d['emisor'], $d['dte'], $d['intercambio']);
    }
    $f = new \sowerphp\general\View_Helper_Form(false);
    array_unshift($documentos, ['Documento', 'Folio', 'Emisor', 'Fecha', 'Total', 'Usuario', 'Acciones']);
    $t = new \sowerphp\general\View_Helper_Table();
    $t->setColsWidth([null, null, null, null, null, null, 150]);
    echo $t->generate($documentos);
}
