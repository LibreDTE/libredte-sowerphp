<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

namespace website\Dte;

/**
 * Comando para enviar por correo automáticamente los DTE que son electrónicos si
 * el receptor es electrónico y no hay recepción del envío (basta la recepción
 * solamente, ya que aunque no haya acuse de recibo, si hay recepción después de
 * 8 días se asume con acuse de recibo)
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2016-10-12
 */
class Shell_Command_DteEmitidos_Intercambio extends \Shell_App
{

    public function main($desde = 7, $grupo = 'dte_plus', $certificacion = false)
    {
        $this->db = \sowerphp\core\Model_Datasource_Database::get();
        $documentos = $this->getDocumentos($desde, $grupo, $certificacion);
        foreach ($documentos as $documento) {
            $this->enviarDTE($documento, $certificacion);
        }
        $this->showStats();
        return 0;
    }

    private function enviarDTE($d, $certificacion)
    {
        $DteEmitido = new Model_DteEmitido($d['emisor'], $d['dte'], $d['folio'], (int)$certificacion);
        if ($DteEmitido->getEstado()=='R')
            return;
        $this->out('Enviando XML del DTE T'.$DteEmitido->dte.'F'.$DteEmitido->folio.' de '.$DteEmitido->getEmisor()->razon_social.' al correo '.$DteEmitido->getReceptor()->config_email_intercambio_user);
        try {
            $status = $DteEmitido->email();
            if ($status!==true) {
                $this->out('  [error] '.$status['message']);
            }
        } catch (\Exception $e) {
            $this->out('  [error] '.$e->getMessage());
        }
    }

    private function getDocumentos($desde, $grupo, $certificacion)
    {
        if (is_numeric($desde)) {
            $desde = date('Y-m-d', strtotime('-'.$desde.' days'));
        }
        $estados = Model_DteEmitidos::$revision_estados['rechazados'];
        return $this->db->getTable('
            SELECT e.emisor, e.dte, e.folio
            FROM
                dte_emitido AS e
                JOIN dte_tipo AS t ON e.dte = t.codigo
                JOIN contribuyente AS c ON e.emisor = c.rut
                JOIN contribuyente_config AS rc ON e.receptor = rc.contribuyente AND rc.configuracion = \'email\' AND rc.variable = \'intercambio_user\'
                JOIN usuario_grupo AS ug ON c.usuario = ug.usuario
                JOIN grupo AS g ON ug.grupo = g.id
                JOIN contribuyente_config AS cc ON e.emisor = cc.contribuyente AND cc.configuracion = \'emision\' AND cc.variable = \'intercambio_automatico\' AND cc.valor = \'1\'
                LEFT JOIN dte_intercambio_recepcion_dte AS ir ON ir.emisor = e.emisor AND ir.dte = e.dte AND ir.folio = e.folio AND ir.certificacion = e.certificacion
            WHERE
                g.grupo = :grupo
                AND t.enviar = true
                AND e.fecha >= :desde
                AND e.certificacion = :certificacion
                AND e.emisor != e.receptor
                AND e.track_id IS NOT NULL
                AND e.revision_estado IS NOT NULL
                AND SUBSTR(e.revision_estado,1,3) NOT IN (\''.implode('\', \'', $estados).'\')
                AND ir.responde IS NULL
            ORDER BY e.emisor, e.fecha, e.dte, e.folio
        ', [':desde' => $desde, ':grupo' => $grupo, ':certificacion' => (int)$certificacion]);
    }

}
