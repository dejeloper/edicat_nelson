<?php

defined('BASEPATH') OR exit('No direct script access allowed');
 
function Deuda($CodPedido = null) {    
    //Datos Auditoría
    // $user = $this->session->userdata('Usuario');  
    $CI = get_instance();
    $user = $CI->session->userdata('Usuario');
    $fecha = date("Y-m-d H:i:s");
    //$CI->Pagos_model->updateEspaciosPagosProg();

    $dataPedidos = $CI->Pedidos_model->obtenerPedidosActivos($CodPedido);
    if (isset($dataPedidos) && $dataPedidos != FALSE) {
        foreach ($dataPedidos as $value) { 
            if ($value["Saldo"] > 0) {
                $EstCliente = $value["EstCliente"];
                $fechaPago = date("Y-m-d", strtotime($value["DiaCobro"]));
                $fecha = date("Y-m-d", strtotime($fecha));
                $interval = date_diff(date_create($fechaPago), date_create($fecha));
                $diferencia = intval($interval->format('%a'));
                $signo = $interval->format('%R');
                if ($signo == "-") {
                    $diferencia = $diferencia * -1;
                }

                $cambio = 0;

                if ($diferencia <= 10) {
                    $estado = "Al día";
                    $CodEstadoPed = 111;
                    $CodEstadoCli = 104;
                } else if ($diferencia >= 11 && $diferencia < 45) {
                    $estado = "Debe";
                    $CodEstadoPed = 112;
                    $CodEstadoCli = 105;
                } else if ($diferencia >= 45 && $diferencia < 90) {
                    $estado = "En Mora";
                    $CodEstadoPed = 112;
                    $CodEstadoCli = 124;
                } else if ($diferencia >= 90) {
                    $estado = "DataCrédito";
                    $CodEstadoPed = 125;
                    $CodEstadoCli = 115;
                }

                $cambio = ($CodEstadoCli != $EstCliente) ? 1 : 0;


                if ($cambio > 0) {
                    cambiarEstadoPedidoDeuda($value["Codigo"], $CodEstadoPed, $estado);
                    cambiarEstadoClienteDeuda($value["Cliente"], $CodEstadoCli, $value["Codigo"], $estado);
                    $val = $val = $CI->Pedidos_model->valPedido($value["Codigo"]);
                    if ($val == FALSE) {
                        //Crear Registro Validacion Deuda                            
                        valDeudaSave($value["Codigo"], $value["Cliente"], $diferencia, $estado);
                    } else {
                        valDeudaUpdate($val[0]["Codigo"], $diferencia, $estado);
                    }
                }
            }

            validarLlamadas($value["Codigo"], $value["Cliente"]);
        }
    }
    return 1;
}

function validarLlamadas($pedido, $cliente)
{ 
    //Datos Auditoría
    //$user = $this->session->userdata('Usuario');
    $CI = get_instance();
    $user = $CI->session->userdata('Usuario');
    $fecha = date("Y-m-d H:i:s");
    $habilitado = "1"; // Habilitado
    $motivo = "*";  // Todas las razones // "101";
    
    // $llamadas = $CI->Cobradores_model->obtenerLlamadasPedidoPendientes('5066', '5066', $habilitado, $motivo);
    $llamadas = $CI->Cobradores_model->obtenerLlamadasPedidoPendientes($pedido, $cliente, $habilitado, $motivo);
     
    if (isset($llamadas) && $llamadas != FALSE) {
        foreach($llamadas as $val) {   
            $fecha1 = date("Y-m-d");
            $diasComparacion = 1;   
            if ($val["Motivo"] == '101') {
                $fecha1 = date("Y-m-d", strtotime($val["Fecha"]));  
                $diasComparacion = 8;                  
            } else   
            if ($val["Motivo"] == '103') {
                $fecha1 = date("Y-m-d", strtotime($val["Fecha"]));                 
                $diasComparacion = 1;                  
            } else   
            if ($val["Motivo"] == '104') {
                $fecha1 = date("Y-m-d", strtotime($val["FechaProgramada"]));                 
                $diasComparacion = 1;                  
            } 

            $fecha2 = date("Y-m-d");           
            $interval = date_diff(date_create($fecha1), date_create($fecha2)); 
            $diferencia = intval($interval->format('%a')); 
            $signo = $interval->format('%R'); 
             
            // echo intval($interval->format('%R%a')); 
            if ($signo == "+" and $diferencia >= $diasComparacion){ 

                $llamada = array(
                    "Habilitado" => 0,
                    "UsuarioModificacion" => $user,
                    "FechaModificacion" => $fecha
                );
                $CI->Pagos_model->inhabilitarLlamadasCodigo($val["Codigo"], $llamada);
            }
        } 
    } 
    //  die();
}

function cambiarEstadoPedidoDeuda($pedido, $estado, $est) { 
    //Datos Auditoría
    //$user = $this->session->userdata('Usuario');
    $CI = get_instance();
    $user = $CI->session->userdata('Usuario');
    $fecha = date("Y-m-d H:i:s");

    $dataP = array(
        "Estado" => $estado,
        "UsuarioModificacion" => $user,
        "FechaModificacion" => $fecha
    );

    try {
        if ($CI->Pedidos_model->update($pedido, $dataP)) {
            $datapedido = $CI->Pedidos_model->obtenerPedido($pedido);
            $modulo = "Deuda Pedido";
            $tabla = "Pedido";
            $accion = "Cambio Estado Pedido";
            $data = compararCambiosLog($datapedido, $dataP);
            //var_dump($data);
            if (count($data) > 2) {
                $data['Codigo'] = $pedido;
                $data['Observaciones'] = "Estado: " . $est . "\n---\nSe actualiza estado del Pedido de forma automática\n \nObservación automática.";
                $llave = $pedido;
                $sql = LogSave($data, $modulo, $tabla, $accion, $llave);
            }
        } else {
            return "No se pudo Actualizar el Pedido.";
        }
    } catch (Exception $e) {
        return 'Ha habido una excepción: ' . $e->getMessage() . "<br>";
    }
}
 
function cambiarEstadoClienteDeuda($cliente, $estado, $pedido, $est) {
    //Datos Auditoría
    //$user = $this->session->userdata('Usuario');    
    $CI = get_instance();
    $user = $CI->session->userdata('Usuario');
    $fecha = date("Y-m-d H:i:s");

    $dataC = array(
        "Estado" => $estado,
        "UsuarioModificacion" => $user,
        "FechaModificacion" => $fecha
    );

    try {
        if ($CI->Clientes_model->update($cliente, $dataC)) {
            $dataClientes = $CI->Clientes_model->obtenerCliente($cliente);
            $modulo = "Deuda Pedido";
            $tabla = "Pedido";
            $accion = "Cambio Estado Cliente";
            $data = compararCambiosLog($dataClientes, $dataC);
            //var_dump($data);
            if (count($data) > 2) {
                $data['Codigo'] = $pedido;
                $data['Observaciones'] = "Estado: " . $est . "\n---\nSe actualiza estado del Cliente de forma automática\n \nObservación automática.";
                $llave = $pedido;
                $sql = LogSave($data, $modulo, $tabla, $accion, $llave);
            }
        } else {
            return "No se pudo Actualizar el Cliente.";
        }
    } catch (Exception $e) {
        return 'Ha habido una excepción: ' . $e->getMessage() . "<br>";
    }
}

function valDeudaUpdate($validacion, $dia, $estado) {
    //Datos Auditoría
    //$user = $this->session->userdata('Usuario');    
    $CI = get_instance();
    $user = $CI->session->userdata('Usuario');
    $fecha = date("Y-m-d H:i:s");
    $fechaValidacion = date("Y-m-d");

    $data = array(
        "FechaValidacion" => $fechaValidacion,
        "Dias" => $dia,
        "Estado" => $estado,
        "Observaciones" => "Validación de Deuda y Cambio de Estado\nNuevo Estado: " . $estado . "\n\nObservación Automática.",
        "UsuarioModificacion" => $user,
        "FechaModificacion" => $fecha
    );
    try {
        $CI->Pedidos_model->updateValDeuda($validacion, $data);
    } catch (Exception $e) {
        return 'Ha habido una excepción: ' . $e->getMessage() . "<br>";
    }
}

function valDeudaSave($pedido, $cliente, $dia, $estado) {
    //Datos Auditoría
    //$user = $this->session->userdata('Usuario');
    $CI = get_instance();
    $user = $CI->session->userdata('Usuario');
    $fecha = date("Y-m-d H:i:s");
    $fechaValidacion = date("Y-m-d");

    $data = array(
        "Pedido" => $pedido,
        "Cliente" => $cliente,
        "FechaValidacion" => $fechaValidacion,
        "Dias" => $dia,
        "Estado" => $estado,
        "Observaciones" => "Validación de Deuda y Cambio de Estado\nNuevo Estado: " . $estado . "\n\nObservación Automática.",
        "UsuarioCreacion" => $user,
        "FechaCreacion" => $fecha
    );
    try { 
        $CI->Pedidos_model->saveValDeuda($data);
    } catch (Exception $e) {
        return 'Ha habido una excepción: ' . $e->getMessage() . "<br>";
    }
}


?>