<?php

defined('BASEPATH') OR exit('No direct script access allowed'); 
require_once APPPATH.'libraries/dompdf/autoload.inc.php';  
use Dompdf\Dompdf;

class Pagos extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->viewControl = 'Pagos';
        $this->load->model('Usuarios_model');
        $this->load->model('Pagos_model');
        $this->load->model('Clientes_model');
        $this->load->model('Pedidos_model');
        $this->load->model('Estados_model');
        $this->load->model('Cobradores_model');

        if (!$this->session->userdata('Login')) {
            $this->session->set_flashdata("error", "Debe iniciar sesión antes de continuar. Después irá a: http://" . $_SERVER[HTTP_HOST] . $_SERVER[REQUEST_URI]);
            $url = str_replace("/", "|", $_SERVER["REQUEST_URI"]);
            redirect(site_url("Login/index/" . substr($url, 1)));
        } else {
            Deuda();
        }
    }

    public function index() {
        redirect(site_url($this->viewControl . "/Admin/"));
    }
 
    public function Admin() {
        $idPermiso = 22;
        $page = validarPermisoPagina($idPermiso);
        
        $datosMotivos = $this->Cobradores_model->obtenerMotivosLlamadas();
        
        $dataCobradores = $this->Cobradores_model->obtenerCobradores();

        $data = new stdClass();
        $data->Controller = "Pagos";
        $data->title = "Llamadas del Día";
        $data->subtitle = "Clientes para gestión de Cobro";
        $data->contenido = $this->viewControl . '/Admin';
        $data->Lista1 = $datosMotivos;

        $this->load->view('frontend', $data);
    }

    public function NoLlamada() {
        $idPermiso = 23;
        $page = validarPermisoPagina($idPermiso);
        $datosMotivos = $this->Cobradores_model->obtenerMotivosLlamadas();

        $data = new stdClass();
        $data->Controller = "Pagos";
        $data->title = "Clientes Sin Llamar";
        $data->subtitle = "Clientes para gestión de Cobro";
        $data->contenido = $this->viewControl . '/NoLlamada';
        $data->Lista1 = $datosMotivos;

        $this->load->view('frontend', $data);
    }

    public function Admin2() {
        $datosPagos = $this->obtenerListadosClientesCobro();

        $datosMotivos = $this->Cobradores_model->obtenerMotivosLlamadas();
        if ($datosMotivos == FALSE) {
            $datosMotivos = array(
                "Codigo" => "101",
                "Nombre" => "Pendiente",
                "Color" => ""
            );
        }

        $data = new stdClass();
        $data->Controller = "Pagos";
        $data->title = "Listado de Próximos Pagos";
        $data->subtitle = "Clientes para gestión de Cobro";
        $data->contenido = $this->viewControl . '/Admin2';
        $data->datosPagos = $datosPagos;
        $data->Lista1 = $datosMotivos;

        $this->load->view('frontend', $data);
    }

    public function Cliente($cliente) {
        $idPermiso = 11;
        $page = validarPermisoPagina($idPermiso);

        $dataClientes = $this->Clientes_model->obtenerCliente($cliente);
        if (isset($dataClientes) && $dataClientes != FALSE) {
            $dataPago = $this->Pagos_model->obtenerPagosCliente($cliente);
            //var_dump($dataPago);

            $data = new stdClass();
            $data->Controller = "Pagos";
            $data->title = "Pagos de " . $dataClientes[0]["Nombre"];
            $data->subtitle = "Pagos de " . $dataClientes[0]["Nombre"];
            $data->contenido = $this->viewControl . '/Cliente';
            $data->cliente = $cliente;

            if (isset($dataPago) && $dataPago != FALSE) {
                $data->ListaDatos = $dataPago;
                $data->pedido = $dataPago[0]["Pedido"];
                $dataPedido = $this->Pedidos_model->obtenerPedido($dataPago[0]["Pedido"]);
                if (isset($dataPedido) && $dataPedido != FALSE) {
                    $data->saldo = $dataPedido[0]["Saldo"];
                }
            } else {
                $data->ListaDatos = null;
                $dataPedido = $this->Pedidos_model->obtenerPedidoPorCliente($cliente);
                if (isset($dataClientes) && $dataClientes != FALSE) {
                    $data->pedido = $dataPedido[0]["Codigo"];
                    $data->saldo = $dataPedido[0]["Saldo"];
                }
            }
            $data->ListaDatos2 = $dataClientes;

            if ($dataClientes[0]["Estado"] == 123) {
                $this->session->set_flashdata("msg", "El Cliente <b>" . $dataClientes[0]["Nombre"] . "</b> pagó el total de la Biblia.");
            }

            $this->load->view('frontend', $data);
        } else {
            $this->session->set_flashdata("error", "No se encontraron datos del Cliente: <b>$cliente</b>");
            redirect(base_url("/Clientes/Admin/"));
        }
    }

    public function Generar($cliente) {
        $idPermiso = 10;
        $page = validarPermisoPagina($idPermiso);

        $dataClientes = $this->Clientes_model->obtenerClienteDir($cliente);
        if (isset($dataClientes) && $dataClientes != FALSE) {
            $dataPedido = $this->Pedidos_model->obtenerPedidosCliente($cliente);
            if (isset($dataPedido) && $dataPedido != FALSE) {

                $dataPagoPedido = array();
                foreach ($dataPedido as $item) {
                    $i = intval($item['Codigo']);
                    $dataPagos = $this->Pagos_model->obtenerPagosPedido($i);
                    if (isset($dataPagos) && $dataPagos != FALSE) {
                        $dataPagoPedido[$i] = $dataPagos;
                    }
                }

                $datosPagos = array();
                $dataPagosP = array();
                foreach ($dataPedido as $item1) {
                    $i = intval($item1['Codigo']);
                    if (array_key_exists($i, $dataPagoPedido)) {
                        $dataPagosP["Pedidos"] = $i;
                        $cuota = 0;
                        $abonado = 0;
                        $f2 = "2018-01-01 00:00:00";
                        foreach ($dataPagoPedido[$i] as $item) {
                            $cuota ++;
                            $abonado = $abonado + $item["Pago"];
                            $f1 = $item["FechaPago"];
                            if ($f1 > $f2) {
                                $f2 = $f1;
                            }
                        }
                        if ($item1["ValCuota"] == "0") {
                            $num = $this->Pagos_model->ultimaCuota($i);
                            $cuota = $num[0]["Cuota"];
                        }
                        $dataPagosP["cuota"] = $cuota;
                        $dataPagosP["abonado"] = $abonado;
                        $dataPagosP["valor"] = $item1["Valor"];
                        $dataPagosP["saldo"] = intval($item1["Valor"]) - intval($abonado);
                        $dataPagosP["fechaUltimoPago"] = $f2;
                        $ultimoPago = 0;

                        foreach ($dataPagoPedido[$i] as $item) {
                            if ($item["FechaPago"] == $f2) {
                                if ($item["Pago"] > $ultimoPago) {
                                    $ultimoPago = $item["Pago"];
                                }
                            }
                        }
                        $dataPagosP["UltimoPago"] = $ultimoPago;
                        $datosPagos[$i] = $dataPagosP;
                    } else {
                        $dataPagosP["Pedidos"] = $i;
                        $dataPagosP["cuota"] = "0";
                        $dataPagosP["abonado"] = "0";
                        $dataPagosP["valor"] = $item1["Valor"];
                        $dataPagosP["saldo"] = intval($item1["Valor"]);
                        $dataPagosP["fechaUltimoPago"] = "--";
                        $ultimoPago = 0;
                        $dataPagosP["UltimoPago"] = 0;
                        $datosPagos[$i] = $dataPagosP;
                    }
                }

                $data = new stdClass();
                $data->Controller = "Pagos";
                $data->title = "Hacer Recibo";
                $data->subtitle = "Hacer nuevo Recibo a Cliente";
                $data->contenido = $this->viewControl . '/Generar';
                $data->cliente = $cliente;
                $data->ListaDatos = $dataPedido;
                $data->ListaDatos2 = $dataClientes;
                $data->ListaDatos3 = $datosPagos;

                $this->load->view('frontend', $data);
            } else {
                $this->session->set_flashdata("error", "No se encontraron pedidos del Cliente: <b>$cliente</b>");
                redirect(base_url("/Clientes/Admin/"));
            }
        } else {
            $this->session->set_flashdata("error", "No se encontraron datos del Cliente: <b>$cliente</b>");
            redirect(base_url("/Clientes/Admin/"));
        }
    }

    public function SchedulePayment() {
        $idPermiso = 10;
        $page = validarPermisoAcciones($idPermiso);
        if ($page) {
            //Datos Pago Programado
            $pag_ped = trim($this->input->post('pag_ped'));
            $pag_pag = trim($this->input->post('pag_pag'));
            $pag_fec = trim($this->input->post('pag_fec') . " 00:00:00");
            $pag_fec = preg_replace('#(\d{2})/(\d{2})/(\d{4})\s(.*)#', '$3-$2-$1 $4', $pag_fec);
            $pag_obs = trim($this->input->post('pag_obs'));
            //Datos Historial
            $no_money = array("$", ".", " ");
            $pag_sal = trim($this->input->post('pag_sal'));
            $pag_sal = str_replace($no_money, "", $pag_sal);
            $pag_cuo = trim($this->input->post('pag_cuo'));
            $pag_cli = trim($this->input->post('pag_cli'));

            //Datos Auditoría
            $user = $this->session->userdata('Usuario');
            $fecha = date("Y-m-d H:i:s");

            //Programar Pago
            $dataPago = array(
                "Pedido" => $pag_ped,
                "Cuota" => $pag_pag,
                "FechaProgramada" => $pag_fec,
                "Estado " => 116,
                "Observaciones" => $pag_obs,
                "Habilitado" => 1,
                "UsuarioCreacion" => $user,
                "FechaCreacion" => $fecha
            );

            try {
                if ($this->Pagos_model->saveProg($dataPago)) {
                    $Pag = $this->Pagos_model->obtenerPagosProgramaPedidoPagoUserFec($pag_ped, $pag_fec, $user, $fecha);
                    if ($Pag) {
                        $dataPago['Codigo'] = $Pag[0]['Codigo'];
                        $dataPago['Observaciones'] = "Estado: Recibo de Pago\n---\n" . $dataPago['Observaciones'];
                        $modulo = "Pagar Pedido";
                        $tabla = "PagosProgramados";
                        $accion = "Programar Pago";
                        $llave = $pag_ped;
                        //Se Crea Historial Pago
                        $this->History($pag_cli, $pag_ped, $fecha, $user, "Programar Pago", $pag_sal, 0, $pag_sal, $pag_pag, $pag_obs);

                        $sql = LogSave($dataPago, $modulo, $tabla, $accion, $llave);
                        echo 1;
                    } else {
                        $errores++;
                        $lblErrores = "No se pudo guardar, por favor intentelo de nuevo.";
                    }
                } else {
                    $errores++;
                    $lblErrores = "No se pudo guardar, por favor intentelo de nuevo.";
                }
            } catch (Exception $e) {
                $errores++;
                $lblErrores = 'Ha habido una excepción: ' . $e->getMessage() . "<br>";
            }
        } else {
            echo "No tiene permisos para Programar Pago";
        }
    }

    public function Programados($pedido) {
        $idPermiso = 14;
        $page = validarPermisoPagina($idPermiso);

        $dataPedido = $this->Pedidos_model->obtenerProductosPedidoCliente($pedido);
        if (isset($dataPedido) && $dataPedido != FALSE) {
            $dataProgramados = $this->Pagos_model->obtenerPagosProgramaPedido($pedido);
            $eProgra = $this->valPagosProgramados($pedido, $dataProgramados);

            if (intval($eProgra) >= 1) {
                $this->session->set_flashdata("error", "Se Descartaron " . $eProgra . " Pagos por vencimiento de fecha.");
                redirect(base_url("/Pagos/Programados/" . $pedido . "/"));
            } else if ($eProgra == 0) {
                $data = new stdClass();
                $data->Controller = "Pagos";
                $data->title = "Recibos de Pago";
                $data->subtitle = "Recibos de Pago por Pedido";
                $data->contenido = $this->viewControl . '/Programados';
                $data->pedido = $pedido;
                $data->cliente = $dataPedido[0]["Cliente"];
                if (isset($dataProgramados) && $dataProgramados != FALSE) {
                    $data->ListaDatos = $dataProgramados;
                } else {
                    $data->ListaDatos = array();
                }
                $data->ListaDatos2 = $dataPedido;

                $this->load->view('frontend', $data);
            } else {
                $this->session->set_flashdata("error", "No se Pudo validar correctamente el Pago.");
                redirect(base_url("/Pagos/Programados/" . $pedido . "/"));
            }
        } else {
            $this->session->set_flashdata("error", "No se puede acceder a los datos del Pedido. Recargue la página e intente de nuevo.");
            redirect(base_url("/Clientes/Admin/"));
        }
    }

    public function ProgramadosPaz($pedido) {
        $dataPedido = $this->Pedidos_model->obtenerProductosPedidoClienteAll($pedido);
        if (isset($dataPedido) && $dataPedido != FALSE) {
            $dataProgramados = $this->Pagos_model->obtenerPagosProgramaPedido($pedido);

            $eProgra = $this->valPagosProgramados($pedido, $dataProgramados);
            if (intval($eProgra) >= 1) {
                $this->session->set_flashdata("error", "Se Descartaron " . $eProgra . " Pagos por vencimiento de fecha.");
                redirect(base_url("/Pagos/Programados/" . $pedido . "/"));
            } else if ($eProgra == 0) {
                $datacliente = $this->Clientes_model->obtenerCliente($dataPedido[0]["Cliente"]);

                $data = new stdClass();
                $data->Controller = "Pagos";
                $data->title = "Recibos de Pago";
                $data->subtitle = "Recibos de Pago por Pedido";
                $data->contenido = $this->viewControl . '/ProgramadosPaz';
                $data->pedido = $pedido;
                $data->cliente = $dataPedido[0]["Cliente"];
                if (isset($dataProgramados) && $dataProgramados != FALSE) {
                    $data->ListaDatos = $dataProgramados;
                } else {
                    $data->ListaDatos = array();
                }
                $data->ListaDatos2 = $dataPedido;
                $this->session->set_flashdata("msg", "El Cliente <b>" . $datacliente[0]["Nombre"] . "</b> pagó el total de la Biblia.");

                $this->load->view('frontend', $data);
            } else {
                $this->session->set_flashdata("error", "No se Pudo validar correctamente el Pago.");
                redirect(base_url("/Pagos/Programados/" . $pedido . "/"));
            }
        } else {
            $this->session->set_flashdata("error", "No se puede acceder a los datos del Pedido. Recargue la página e intente de nuevo.");
            redirect(base_url("/Clientes/Admin/"));
        }
    }

    public function valPagosProgramados($pedido, $dataProgramados) {
        $fecha = date("Y-m-d");
        $fecha = date("Y-m-d", strtotime($fecha . "- 60 days"));
        $con = 0;

        if (isset($dataProgramados) && $dataProgramados != FALSE) {
            foreach ($dataProgramados as $item) {
                if ($item["Estado"] == 116) { //Pago Programado
                    $datetime = date("Y-m-d", strtotime($item["FechaProgramada"]));

                    $datetime1 = date_create($datetime);
                    $datetime2 = date_create($fecha);
                    $interval = date_diff($datetime1, $datetime2);
                    $interval->format('%R%a');
                    $val = intval($interval->format('%R%a'));

                    if ($val >= 0) {
                        //Datos Auditoría
                        $user = $this->session->userdata('Usuario');
                        $fecha = date("Y-m-d H:i:s");

                        //Busqueda de pago 
                        $pag_pro = $item["Codigo"];
                        $dataPagosPro = $this->Pagos_model->obtenerPagosProgramaCod($pag_pro);

                        //Descartar Pago (Actualizar pago programado No pagado)
                        $dataPago = array(
                            "Estado" => 122, //Descartado
                            "Observaciones" => $dataPagosPro[0]["Observaciones"] . "\n---\nSe descarta Pago:\nSe venció la fecha del recibo y cliente no generó pago. \nDescarte automático.",
                            "Habilitado" => 1,
                            "UsuarioModificacion" => $user,
                            "FechaModificacion" => $fecha
                        );

                        try {
                            if ($this->Pagos_model->updateProg($pag_pro, $dataPago)) {
                                $modulo = "Pagar Pedido";
                                $tabla = "PagosProgramados";
                                $accion = "Descartar Recibo de Pago Automática";
                                $data = compararCambiosLog($dataPagosPro, $dataPago);
                                //var_dump($data);
                                if (count($data) > 2) {
                                    $data['Codigo'] = $pag_pro;
                                    $data['Observaciones'] = "Estado: Descartado\n---\nSe actualiza estado del Recibo de Pago de forma automática\n \nObservación automática.";
                                    $llave = $pedido;
                                    $sql = LogSave($data, $modulo, $tabla, $accion, $llave);
                                    $con++;
                                }
                            } else {
                                return "No se pudo Actualizar el Recibo de Pago. Actualice la página y vuelva a intentarlo.";
                            }
                        } catch (Exception $e) {
                            return 'Ha habido una excepción: ' . $e->getMessage() . "<br>";
                        }
                    }
                }
            }
        }
        if ($con >= 1) {
            return $con;
        }
        return 0;
    }

    public function Validar($pagoProgramado) {
        $idPermiso = 15;
        $page = validarPermisoPagina($idPermiso);

        $dataPago = $this->Pagos_model->obtenerPagosProgramaCod($pagoProgramado);
        if (isset($dataPago) && $dataPago != FALSE) {
            $dataPedido = $this->Pedidos_model->obtenerProductosPedidoClienteAll($dataPago[0]["Pedido"]);
            if (isset($dataPedido) && $dataPedido != FALSE) {
                $dataClientes = $this->Clientes_model->obtenerClienteDir($dataPedido[0]["Cliente"]);
                if (isset($dataClientes) && $dataClientes != FALSE) {
                    $dataPagoPedido = array();
                    foreach ($dataPedido as $item) {
                        $i = intval($item['CodPedido']);
                        $dataPagos = $this->Pagos_model->obtenerPagosPedido($i);
                        if (isset($dataPagos) && $dataPagos != FALSE) {
                            $dataPagoPedido[$i] = $dataPagos;
                        }
                    }

                    $datosPagos = array();
                    $dataPagosP = array();
                    foreach ($dataPedido as $item1) {
                        $i = intval($item1['CodPedido']);
                        if (array_key_exists($i, $dataPagoPedido)) {
                            $dataPagosP["Pedidos"] = $i;
                            $cuota = 0;
                            $abonado = 0;
                            $f2 = "2018-01-01 00:00:00";
                            foreach ($dataPagoPedido[$i] as $item) {
                                $cuota ++;
                                $abonado = $abonado + $item["Pago"];
                                $f1 = $item["FechaPago"];
                                if ($f1 > $f2) {
                                    $f2 = $f1;
                                }
                            }
                            $dataPagosP["cuota"] = $cuota;
                            $dataPagosP["abonado"] = $abonado;
                            $dataPagosP["valor"] = $item1["Valor1"];
                            $dataPagosP["saldo"] = intval($item1["Valor1"]) - intval($abonado);
                            $dataPagosP["fechaUltimoPago"] = $f2;
                            $ultimoPago = 0;

                            foreach ($dataPagoPedido[$i] as $item) {
                                if ($item["FechaPago"] == $f2) {
                                    if ($item["Pago"] > $ultimoPago) {
                                        $ultimoPago = $item["Pago"];
                                    }
                                }
                            }
                            $dataPagosP["UltimoPago"] = $ultimoPago;
                            $datosPagos[$i] = $dataPagosP;
                        } else {
                            $dataPagosP["Pedidos"] = $i;
                            $dataPagosP["cuota"] = "0";
                            $dataPagosP["abonado"] = "0";
                            $dataPagosP["valor"] = $item1["Valor1"];
                            $dataPagosP["saldo"] = intval($item1["Saldo"]);
                            $dataPagosP["fechaUltimoPago"] = "--";
                            $ultimoPago = 0;
                            $dataPagosP["UltimoPago"] = 0;
                            $datosPagos[$i] = $dataPagosP;
                        }
                    }

                    $data = new stdClass();
                    $data->Controller = "Pagos";
                    $data->title = "Validar Pago";
                    $data->subtitle = "Validar Recibo de Pago";
                    $data->contenido = $this->viewControl . '/Validar';
                    $data->cliente = $dataPedido[0]["Cliente"];
                    $data->ListaDatos = $dataPedido;
                    $data->ListaDatos2 = $dataClientes;
                    $data->ListaDatos3 = $datosPagos;
                    $data->dataPago = $dataPago;

                    $this->load->view('frontend', $data);
                } else {
                    $this->session->set_flashdata("error", "No se encontraron datos del Cliente: <b>$cliente</b>");
                    redirect(base_url("/Clientes/Admin/"));
                }
            } else {
                $this->session->set_flashdata("error", "No se encontraron datos del Pedido del Pago: <b>$cliente</b>");
                redirect(base_url("/Clientes/Admin/"));
            }
        } else {
            $this->session->set_flashdata("error", "No se encontraron datos de la Programación del Pago");
            redirect(base_url("/Pagos/Admin/"));
        }
    }

    public function numCuotas($pedido) {
        $dataPagos = $this->Pagos_model->ultimaCuota($pedido); 
        return $dataPagos;
    }

    public function Confirmar($pagoProgramado) {
        $idPermiso = 16;
        $page = validarPermisoPagina($idPermiso);

        $dataPago = $this->Pagos_model->obtenerPagosProgramaCod($pagoProgramado);
        if (isset($dataPago) && $dataPago != FALSE) {
            $dataPedido = $this->Pedidos_model->obtenerProductosPedidoCliente($dataPago[0]["Pedido"]);
            if (isset($dataPedido) && $dataPedido != FALSE) {
                $dataClientes = $this->Clientes_model->obtenerClienteDir($dataPedido[0]["Cliente"]);
                if (isset($dataClientes) && $dataClientes != FALSE) {
                    $dataCobradores = $this->Cobradores_model->obtenerCobradores();
                    if (isset($dataCobradores) && $dataCobradores != FALSE) {

                        $dataPagoPedido = array();
                        foreach ($dataPedido as $item) {
                            $i = intval($item['CodPedido']);
                            $dataPagos = $this->Pagos_model->obtenerPagosPedido($i);
                            if (isset($dataPagos) && $dataPagos != FALSE) {
                                $dataPagoPedido[$i] = $dataPagos;
                            }
                        }

                        $datosPagos = array();
                        $dataPagosP = array();
                        foreach ($dataPedido as $item1) {
                            $i = intval($item1['CodPedido']);
                            if (array_key_exists($i, $dataPagoPedido)) {
                                $dataPagosP["Pedidos"] = $i;
                                //echo "<script>alert(".$item1["ValCuota"].");</script>";
                                $cuota = 0;
                                $abonado = 0;
                                $f2 = "2018-01-01 00:00:00";
                                foreach ($dataPagoPedido[$i] as $item) {
                                    $cuota ++;
                                    $abonado = $abonado + $item["Pago"];
                                    $f1 = $item["FechaPago"];
                                    if ($f1 > $f2) {
                                        $f2 = $f1;
                                    }
                                }
                                //echo "<script>alert('Holi '+".$dataPedido[0]["Valor"].");</script>";
                                if ($item1["ValCuota"] == "0") {
                                    $num = $this->Pagos_model->ultimaCuota($i);
                                    $cuota = $num[0]["Cuota"];
                                }
                                $dataPagosP["cuota"] = $cuota;
                                $dataPagosP["abonado"] = $abonado;
                                $dataPagosP["valor"] = $item1["Valor1"];
                                $dataPagosP["saldo"] = intval($item1["Valor1"]) - intval($abonado);
                                $dataPagosP["fechaUltimoPago"] = $f2;
                                $ultimoPago = 0;

                                foreach ($dataPagoPedido[$i] as $item) {
                                    if ($item["FechaPago"] == $f2) {
                                        if ($item["Pago"] > $ultimoPago) {
                                            $ultimoPago = $item["Pago"];
                                        }
                                    }
                                }
                                $dataPagosP["UltimoPago"] = $ultimoPago;
                                $datosPagos[$i] = $dataPagosP;
                            } else {
                                $dataPagosP["Pedidos"] = $i;
                                $dataPagosP["cuota"] = "0";
                                $dataPagosP["abonado"] = "0";
                                $dataPagosP["valor"] = $item1["Valor1"];
                                $dataPagosP["saldo"] = intval($item1["Saldo"]);
                                $dataPagosP["fechaUltimoPago"] = "--";
                                $ultimoPago = 0;
                                $dataPagosP["UltimoPago"] = 0;
                                $datosPagos[$i] = $dataPagosP;
                            }
                        }
                        
                        $proximoPago = $this->getNextDayPay($dataPedido[0]["DiaCobro"]);

                        $data = new stdClass();
                        $data->Controller = "Pagos";
                        $data->title = "Confirmar Pago";
                        $data->subtitle = "Confirmar Pago";
                        $data->contenido = $this->viewControl . '/Confirmar';
                        $data->cliente = $dataPedido[0]["Cliente"];
                        $data->pedido = $dataPedido[0]["CodPedido"];
                        $data->valor = $dataPedido[0]["Valor1"];
                        $data->proximoPago = $proximoPago;
                        $data->codigo = $pagoProgramado;
                        $data->ListaDatos = $dataPedido;
                        $data->ListaDatos2 = $dataClientes;
                        $data->ListaDatos3 = $datosPagos;
                        $data->dataPago = $dataPago;
                        $data->Lista1 = $dataCobradores;

                        $this->load->view('frontend', $data);
                    } else {
                        $this->session->set_flashdata("error", "No se encontraron datos de los Cobradores.");
                        redirect(base_url("/Cobradores/Admin/"));
                    }
                } else {
                    $this->session->set_flashdata("error", "No se encontraron datos del Cliente: <b>$cliente</b>");
                    redirect(base_url("/Clientes/Admin/"));
                }
            } else {
                $this->session->set_flashdata("error", "No se encontraron datos del Pedido del Pago: <b>$cliente</b>");
                redirect(base_url("/Clientes/Admin/"));
            }
        } else {
            $this->session->set_flashdata("error", "No se encontraron datos de la Programación del Pago");
            redirect(base_url("/Pagos/Admin/"));
        }
    }

    public function Confirm() {
        $idPermiso = 16;
        $page = validarPermisoAcciones($idPermiso);
         
        if ($page) {
            //Datos Pago
            $pag_cli = trim($this->input->post('pag_cli'));
            $pag_ped = trim($this->input->post('pag_ped'));
            $pag_cuo = trim($this->input->post('pag_cuo'));
            $pag_pag = trim($this->input->post('pag_pag'));
            $pag_fec = trim($this->input->post('pag_fec') . " 00:00:00");
            $pag_fec = preg_replace('#(\d{2})/(\d{2})/(\d{4})\s(.*)#', '$3-$2-$1 $4', $pag_fec);
            $pag_fec_pro = trim($this->input->post('pag_fec_pro') . " 00:00:00");
            $pag_fec_pro = preg_replace('#(\d{2})/(\d{2})/(\d{4})\s(.*)#', '$3-$2-$1 $4', $pag_fec_pro);
            $pag_pro = trim($this->input->post('pag_pro'));
            $pag_cob = trim($this->input->post('pag_cob'));
            $pag_tot = trim($this->input->post('pag_tot'));
            $pag_obs = trim($this->input->post('pag_obs'));
            $pag_obsAnt = trim($this->input->post('pag_obsAnt'));

            $this->conf($pag_cli, $pag_ped, $pag_cuo, $pag_pag, $pag_fec, $pag_pro, $pag_fec_pro, $pag_cob, $pag_tot, $pag_obs, $pag_obsAnt);
        } else {
            echo "No se pudo confirmar el Recibo de Pago. No tiene los permisos.";
        }
    }

    public function ConfirmarDia() {        
        $idPermiso = 16;
        $page = validarPermisoAcciones($idPermiso);
        if ($page) {
            //Datos Pago
            $pag_pro = trim($this->input->post('codigo'));
            $pag_ped = trim($this->input->post('pedido'));
            $pag_cli = trim($this->input->post('cliente'));
            $no_money = array("$", ".", " ");
            $pag_pag = trim($this->input->post('pago'));
            $pag_pag = str_replace($no_money, "", $pag_pag);
            $pag_fec = trim($this->input->post('FechaPago') . " 00:00:00");
            $pag_fec = preg_replace('#(\d{2})/(\d{2})/(\d{4})\s(.*)#', '$3-$2-$1 $4', $pag_fec);
            $pag_fec_pro = trim($this->input->post('FechaPagoProximo') . " 00:00:00");
            $pag_fec_pro = preg_replace('#(\d{2})/(\d{2})/(\d{4})\s(.*)#', '$3-$2-$1 $4', $pag_fec_pro);
            $pag_tot = trim($this->input->post('valor'));
            $pag_cob = trim($this->input->post('cobrador'));
            $pag_obsAnt = trim($this->input->post('ObservacionesAnt'));
            $pag_obs = trim($this->input->post('observaciones'));

            $num = $this->Pagos_model->ultimaCuota($pag_ped);
            $cuotas = $num[0]["Cuota"];

            $pag_cuo = $cuotas + 1;
            $this->conf($pag_cli, $pag_ped, $pag_cuo, $pag_pag, $pag_fec, $pag_pro, $pag_fec_pro, $pag_cob, $pag_tot, $pag_obs, $pag_obsAnt);
        } else {
            echo "No se pudo confirmar el Recibo de Pago. No tiene los permisos.";
        }
    }

    public function conf($pag_cli, $pag_ped, $pag_cuo, $pag_pag, $pag_fec, $pag_pro, $pag_fec_pro, $pag_cob, $pag_tot, $pag_obs, $pag_obsAnt)
    { 
        //Datos Auditoría
        $user = $this->session->userdata('Usuario');
        $fecha = date("Y-m-d H:i:s");
        
        //Validación del pago antes de realizarlo         
        $valPago = $this->Pagos_model->obtenerValidacionPagosPrevio($pag_cli, $pag_ped, $pag_pag, $pag_pro); 

        if (isset($valPago)){ 
            $numPagosConfirmados = $valPago[0]["Num"]; 
            if ($numPagosConfirmados == 0) {
                //Confirmar Pago (Crear Pago y actualizar pago programado)
                $dataPago = array(
                    "Cliente" => $pag_cli,
                    "Pedido" => $pag_ped,
                    "Cuota" => $pag_cuo,
                    "Pago" => $pag_pag,
                    "Confirmacion" => $pag_pro,
                    "FechaPago" => $pag_fec,
                    "TotalPago" => $pag_tot,
                    "Cobrador" => $pag_cob,
                    "Observaciones" => $pag_obsAnt . "\n---\n" . $pag_obs,
                    "Habilitado" => 1,
                    "UsuarioCreacion" => $user,
                    "FechaCreacion" => $fecha
                ); 

                try {
                    if ($this->Pagos_model->save($dataPago)) {
                        $Pag = $this->Pagos_model->obtenerPagosPedidoUserFec($pag_cli, $pag_ped, $user, $fecha);
                        if ($Pag) {
                            $dataPago['Codigo'] = $Pag[0]['Codigo'];
                            $dataPago['Observaciones'] = "Estado: Pago Realizado\n---\n" . $pag_obs;
                            $modulo = "Pagar Pedido";
                            $tabla = "Pagos";
                            $accion = "Confirmar Pago";
                            $llave = $pag_ped;
                            $sql = LogSave($dataPago, $modulo, $tabla, $accion, $llave);

                            $dataPedido = $this->Pedidos_model->obtenerPedido($pag_ped);
                            if ($pag_fec_pro == "" or $pag_fec_pro == NULL){
                                $DiaCobro = $this->getNextDayPay($dataPedido[0]["DiaCobro"]);
                            } else {
                                $DiaCobro = $pag_fec_pro;
                            }

                            $saldo = intval($dataPedido[0]["Saldo"]) - intval($pag_pag);
                            //Se Crea Historial Pago
                            $this->History($pag_cli, $pag_ped, $fecha, $user, "Confirmar Pago", $dataPedido[0]["Saldo"], $pag_cuo, $saldo, $pag_pag, $pag_obs);

                            if ($saldo <= 0) {
                                $dataActPedido = array(
                                    "DiaCobro" => $DiaCobro,
                                    "Saldo" => $saldo,
                                    "Estado" => 114,
                                    "FechaUltimoPago" => $fecha,
                                    "Observaciones" => "Actualización de Saldo del Pedido:\nSaldo Anterior: " . money_format("%.0n", ($dataPedido[0]["Valor"]))
                                    . "\nSaldo Actual: " . money_format("%.0n", ($saldo)) . "\nEstado: Paz y Salvo\n \nObservación automática.",
                                    "UsuarioModificacion" => $user,
                                    "FechaModificacion" => $fecha
                                );
                            } else {
                                $dataActPedido = array(
                                    "DiaCobro" => $DiaCobro,
                                    "Saldo" => $saldo,
                                    "Estado" => 111,
                                    "FechaUltimoPago" => $fecha,
                                    "Observaciones" => "Actualización de Saldo del Pedido:\nSaldo Anterior: " . money_format("%.0n", ($dataPedido[0]["Valor"]))
                                    . "\nSaldo Actual: " . money_format("%.0n", ($saldo)) . "\nEstado: Pagado\n \nObservación automática.",
                                    "UsuarioModificacion" => $user,
                                    "FechaModificacion" => $fecha
                                );
                            }

                            if ($this->Pedidos_model->update($pag_ped, $dataActPedido)) {
                                $modulo = "Pagar Pedido";
                                $tabla = "Pagos";
                                $accion = "Actualizar Saldo";
                                $data = compararCambiosLog($dataPedido, $dataActPedido);
                                //var_dump($data);
                                if (count($data) > 2) {
                                    $data['Codigo'] = $pag_ped;
                                    $llave = $pag_ped;
                                    $sql = LogSave($data, $modulo, $tabla, $accion, $llave);
                                }

                                $dataPagProg = array(
                                    "Estado" => 117,
                                    "Observaciones" => $pag_obsAnt . "\n---\n" . $pag_obs,
                                    "UsuarioModificacion" => $user,
                                    "FechaModificacion" => $fecha
                                );

                                if ($this->Pagos_model->updateProg($pag_pro, $dataPagProg)) {
                                    $dataPprog = $this->Pagos_model->obtenerPagosProgramaCod($pag_pro);
                                    $modulo = "Pagar Pedido";
                                    $tabla = "PagosProgramados";
                                    $accion = "Actualizar Recibo de Pago";
                                    $data = compararCambiosLog($dataPprog, $dataPagProg);
                                    //var_dump($data);
                                    if (count($data) > 2) {
                                        $data['Codigo'] = $pag_ped;
                                        $data['Observaciones'] = "Estado: Pagado\n---\nSe actualiza estado del Recibo de Pago\n \nObservación automática.";
                                        $llave = $pag_ped;
                                        $sql = LogSave($data, $modulo, $tabla, $accion, $llave);
                                    }
                                    
                                    $this->inhabilitarLlamadas($pag_cli, $pag_ped, $fecha, $user);
                                    $this->Pagos_model->quitarllamadas($pag_cli, $pag_ped);
                                    if ($saldo <= 0) {
                                        $dataCli = array(
                                            "Estado" => 123,
                                            "Observaciones" => $pag_obsAnt . "\n---\nEstado: Paz y Salvo\n---\nCliente queda a Paz y Salvo por Saldo en $ 0\n \nObservación automática.",
                                            "UsuarioModificacion" => $user,
                                            "FechaModificacion" => $fecha
                                        );
                                        if ($this->Clientes_model->update($dataPedido[0]["Cliente"], $dataCli)) {
                                            $dataCliente = $this->Clientes_model->obtenerCliente($dataPedido[0]["Cliente"]);
                                            $modulo = "Pagar Pedido";
                                            $tabla = "Clientes";
                                            $accion = "Paz y Salvo Cliente";
                                            $data = compararCambiosLog($dataCliente, $dataCli);
                                            //var_dump($data);
                                            if (count($data) > 2) {
                                                $data['Codigo'] = $pag_ped;
                                                $data['Observaciones'] = "nEstado: Paz y Salvo\n---\nSe actualiza estado del Cliente\n \nObservación automática.";
                                                $llave = $pag_ped;
                                                $sql = LogSave($data, $modulo, $tabla, $accion, $llave);
                                            }
                                            echo 123;
                                        } else {
                                            echo "No se pudo Actualizar el Estado del Cliente. Actualice la página y vuelva a intentarlo.";
                                        }
                                    } else {
                                        $dataCli = array(
                                            "Estado" => 104,
                                            "UsuarioModificacion" => $user,
                                            "FechaModificacion" => $fecha
                                        );
                                        if ($this->Clientes_model->update($dataPedido[0]["Cliente"], $dataCli)) {
                                            $dataCliente = $this->Clientes_model->obtenerCliente($dataPedido[0]["Cliente"]);
                                            $modulo = "Pagar Pedido";
                                            $tabla = "Clientes";
                                            $accion = "Estado Al día Cliente";
                                            $data = compararCambiosLog($dataCliente, $dataCli);
                                            //var_dump($data);
                                            if (count($data) > 2) {
                                                $data['Codigo'] = $pag_ped;
                                                $data['Observaciones'] = "nEstado: Al día\n---\nSe actualiza estado del Cliente\n \nObservación automática.";
                                                $llave = $pag_ped;
                                                $sql = LogSave($data, $modulo, $tabla, $accion, $llave);
                                            }
                                            echo 1;
                                        } else {
                                            echo "No se pudo Actualizar el Estado del Cliente. Actualice la página y vuelva a intentarlo.";
                                        }
                                    }
                                } else {
                                    echo "No se pudo Actualizar el Recibo de Pago. Actualice la página y vuelva a intentarlo.";
                                }
                            } else {
                                echo "No se pudo Actualizar el Saldo del Pedido. Actualice la página y vuelva a intentarlo.";
                            }
                        } else {
                            echo "No se pudo Confirmar el Pago. Actualice la página y vuelva a intentarlo.";
                        }
                    } else {
                        echo "No se pudo Confirmar el Pago. Actualice la página y vuelva a intentarlo.";
                    }
                } catch (Exception $e) {
                    echo 'Ha habido una excepción: ' . $e->getMessage() . "<br>";
                }
            } else {
                echo "Este pago ya se realizó, por favor confirme los datos.";
            }

            
        } else{
            echo "No se pudo Confirmar el Pago. Actualice la página y vuelva a intentarlo.";
        }
    }

    public function Descartar($pagoProgramado) {        
        $idPermiso = 17;
        $page = validarPermisoPagina($idPermiso);

        $dataPago = $this->Pagos_model->obtenerPagosProgramaCod($pagoProgramado);
        if (isset($dataPago) && $dataPago != FALSE) {
            $dataPedido = $this->Pedidos_model->obtenerProductosPedidoCliente($dataPago[0]["Pedido"]);
            if (isset($dataPedido) && $dataPedido != FALSE) {
                $dataClientes = $this->Clientes_model->obtenerClienteDir($dataPedido[0]["Cliente"]);
                if (isset($dataClientes) && $dataClientes != FALSE) {

                    $dataPagoPedido = array();
                    foreach ($dataPedido as $item) {
                        $i = intval($item['CodPedido']);
                        $dataPagos = $this->Pagos_model->obtenerPagosPedido($i);
                        if (isset($dataPagos) && $dataPagos != FALSE) {
                            $dataPagoPedido[$i] = $dataPagos;
                        }
                    }

                    $datosPagos = array();
                    $dataPagosP = array();
                    foreach ($dataPedido as $item1) {
                        $i = intval($item1['CodPedido']);
                        if (array_key_exists($i, $dataPagoPedido)) {
                            $dataPagosP["Pedidos"] = $i;
                            $cuota = 0;
                            $abonado = 0;
                            $f2 = "2018-01-01 00:00:00";
                            foreach ($dataPagoPedido[$i] as $item) {
                                $cuota ++;
                                $abonado = $abonado + $item["Pago"];
                                $f1 = $item["FechaPago"];
                                if ($f1 > $f2) {
                                    $f2 = $f1;
                                }
                            }
                            $dataPagosP["cuota"] = $cuota;
                            $dataPagosP["abonado"] = $abonado;
                            $dataPagosP["valor"] = $item1["Valor1"];
                            $dataPagosP["saldo"] = intval($item1["Valor1"]) - intval($abonado);
                            $dataPagosP["fechaUltimoPago"] = $f2;
                            $ultimoPago = 0;

                            foreach ($dataPagoPedido[$i] as $item) {
                                if ($item["FechaPago"] == $f2) {
                                    if ($item["Pago"] > $ultimoPago) {
                                        $ultimoPago = $item["Pago"];
                                    }
                                }
                            }
                            $dataPagosP["UltimoPago"] = $ultimoPago;
                            $datosPagos[$i] = $dataPagosP;
                        } else {
                            $dataPagosP["Pedidos"] = $i;
                            $dataPagosP["cuota"] = "0";
                            $dataPagosP["abonado"] = "0";
                            $dataPagosP["valor"] = $item1["Valor1"];
                            $dataPagosP["saldo"] = intval($item1["Saldo"]);
                            $dataPagosP["fechaUltimoPago"] = "--";
                            $ultimoPago = 0;
                            $dataPagosP["UltimoPago"] = 0;
                            $datosPagos[$i] = $dataPagosP;
                        }
                    }

                    $data = new stdClass();
                    $data->Controller = "Pagos";
                    $data->title = "Descartar Pago";
                    $data->subtitle = "Descartar Pago";
                    $data->contenido = $this->viewControl . '/Descartar';
                    $data->cliente = $dataPedido[0]["Cliente"];
                    $data->pedido = $dataPedido[0]["CodPedido"];
                    $data->valor = $dataPedido[0]["Valor1"];
                    $data->codigo = $pagoProgramado;
                    $data->ListaDatos = $dataPedido;
                    $data->ListaDatos2 = $dataClientes;
                    $data->ListaDatos3 = $datosPagos;
                    $data->dataPago = $dataPago;

                    $this->load->view('frontend', $data);
                } else {
                    $this->session->set_flashdata("error", "No se encontraron datos del Cliente: <b>$cliente</b>");
                    redirect(base_url("/Clientes/Admin/"));
                }
            } else {
                $this->session->set_flashdata("error", "No se encontraron datos del Pedido del Pago: <b>$cliente</b>");
                redirect(base_url("/Clientes/Admin/"));
            }
        } else {
            $this->session->set_flashdata("error", "No se encontraron datos de la Programación del Pago");
            redirect(base_url("/Pagos/Admin/"));
        }
    }

    public function Discard() {
        $idPermiso = 17;
        $page = validarPermisoAcciones($idPermiso);
         
        if ($page) {
            //Datos Pago
            $pag_cli = trim($this->input->post('pag_cli'));
            $pag_ped = trim($this->input->post('pag_ped'));
            $pag_pro = trim($this->input->post('pag_pro'));
            $pag_obs = trim($this->input->post('pag_obs'));
            //Datos Historial
            $no_money = array("$", ".", " ");
            $pag_sal = trim($this->input->post('pag_sal'));
            $pag_sal = str_replace($no_money, "", $pag_sal);
            $pag_pag = trim($this->input->post('pag_pag'));
            $pag_pag = str_replace($no_money, "", $pag_pag);
            $pag_cuo = trim($this->input->post('pag_cuo'));
            $pag_Rellam = trim($this->input->post('pag_Rellam') . " 00:00:00");
            $pag_fechaRellam = trim($this->input->post('pag_Rellam'));
            $Fecha_Rellam = preg_replace('#(\d{2})/(\d{2})/(\d{4})\s(.*)#', '$3-$2-$1 $4', $pag_Rellam);

            $this->desc($pag_cli, $pag_ped, $pag_pro, $pag_obs, $pag_sal, $pag_pag, $pag_Rellam, $pag_fechaRellam, $Fecha_Rellam);
        } else {
            echo "No se pudo descartar el Recibo de Pago. No tiene los permisos.";
        }
    }

    public function DescartarDia() {
        $idPermiso = 17;
        $page = validarPermisoAcciones($idPermiso);
         
        if ($page) {
            $pag_cli = trim($this->input->post('cliente'));
            $pag_ped = trim($this->input->post('pedido'));
            $pag_pro = trim($this->input->post('codigo'));
            $pag_obs = trim($this->input->post('observaciones'));
            //Datos Historial
            $no_money = array("$", ".", " ");
            $pag_sal = trim($this->input->post('saldo'));
            $pag_sal = str_replace($no_money, "", $pag_sal);
            $pag_pag = trim($this->input->post('pago'));
            $pag_pag = str_replace($no_money, "", $pag_pag);
            $pag_Rellam = trim($this->input->post('volverLlamar') . " 00:00:00");
            $pag_fechaRellam = trim($this->input->post('volverLlamar'));
            $Fecha_Rellam = preg_replace('#(\d{2})/(\d{2})/(\d{4})\s(.*)#', '$3-$2-$1 $4', $pag_Rellam);

            $this->desc($pag_cli, $pag_ped, $pag_pro, $pag_obs, $pag_sal, $pag_pag, $pag_Rellam, $pag_fechaRellam, $Fecha_Rellam);
        } else {
            echo "No se pudo descartar el Recibo de Pago. No tiene los permisos.";
        }
    }

    public function desc($pag_cli, $pag_ped, $pag_pro, $pag_obs, $pag_sal, $pag_pag, $pag_Rellam, $pag_fechaRellam, $Fecha_Rellam) {
        //Datos Auditoría
        $user = $this->session->userdata('Usuario');
        $fecha = date("Y-m-d H:i:s");

        //Busqueda de pago 
        $dataPagosPro = $this->Pagos_model->obtenerPagosProgramaCod($pag_pro);

        //Descartar Pago (Actualizar pago programado No pagado)
        $dataPago = array(
            "Estado" => 122, //Descartado
            "Observaciones" => $dataPagosPro[0]["Observaciones"] . "\n---\nSe descarta Pago:\n" . $pag_obs,
            "Habilitado" => 1,
            "UsuarioModificacion" => $user,
            "FechaModificacion" => $fecha
        );
 
        try {
            if ($this->Pagos_model->updateProg($pag_pro, $dataPago)) {
                $modulo = "Pagar Pedido";
                $tabla = "PagosProgramados";
                $accion = "Descartar Recibo de Pago";
                $data = compararCambiosLog($dataPagosPro, $dataPago);
                //var_dump($data);
                if (count($data) > 2) {
                    $data['Codigo'] = $pag_pro;
                    $data['Observaciones'] = "Estado: Descartado\n---\nSe actualiza estado del Recibo de Pago\n \nObservación automática.";
                    $llave = $pag_ped;
                    //Se Crea Historial Pago
                    $this->History($pag_cli, $pag_ped, $fecha, $user, "Descartar Pago", $pag_sal, 0, $pag_sal, 0, $pag_obs);

                    $sql = LogSave($data, $modulo, $tabla, $accion, $llave);
                
                    $this->inhabilitarLlamadas($pag_cli, $pag_ped, $fecha, $user);

                    if ($pag_fechaRellam != "") {
                        //Programar Llamada para otro día //
                        $observaciones = "Se descarta el Recibo de Pago.\n---\n" . $pag_obs . "\n\n"
                                . "Se asigna otro día para Llamar al Cliente nuevamente.\n"
                                . "Nuevo día: " . $pag_fechaRellam . ".\n \nObservación Automática";
                        $fechaGestion = date("Y-m-d", strtotime($fecha));
                        $gestion = array(
                            "Pedido" => $pag_ped,
                            "Cliente" => $pag_cli,
                            "Fecha" => $fechaGestion,
                            "Motivo" => 104, //Llamar otro día (black)
                            "Habilitado" => 1,
                            "FechaProgramada" => date("Y-m-d H:i:s", strtotime($Fecha_Rellam)),
                            "Observaciones" => $observaciones,
                            "UsuarioCreacion" => $user,
                            "FechaCreacion" => $fecha
                        );

                        if ($this->Cobradores_model->saveLlamada($gestion)) {
                            $dataGestion = $this->Cobradores_model->obtenerLlamadasPedidoFecha($pag_ped, $pag_cli, $fechaGestion);
                            if ($dataGestion) {
                                $gestion ["Codigo"] = $dataGestion[0]['Codigo'];
                                $gestion ['Observaciones'] = "Gestión de Llamada: Recibo de Pago\n---\n" . $observaciones;
                                $modulo = "Gestión Cliente";
                                $tabla = "Llamada";
                                $accion = "Llamada a Cliente";
                                $llave = $dataGestion[0]['Cliente'];
                                $sql = LogSave($gestion, $modulo, $tabla, $accion, $llave);
                            }
                        }
                    }
                }
                echo 1;
            } else {
                echo "No se pudo Actualizar el Recibo de Pago. Actualice la página y vuelva a intentarlo.";
            }
        } catch (Exception $e) {
            echo 'Ha habido una excepción: ' . $e->getMessage() . "<br>";
        }
    }

    public function obtenerListadosClientesCobro() {
        $dataClientes = $this->Clientes_model->obtenerNomClientesDir($this->config->item('cli_devol'), $this->config->item('cli_paz'));
        $usuario = $this->session->userdata('Codigo');
        $PerfilId = $this->session->userdata('PerfilId');
        
        if (isset($dataClientes) && $dataClientes != FALSE) {
            $d = array();
            $dataPagoPedido = array();
            $datosPagos = array();
            $dataPagosP = array();
            $permisos = $this->SearchPermissions();  
            foreach ($dataClientes as $itemCliente) {
                $diaCobroPedido = "";
                $cliente = $itemCliente["Codigo"]; 
                $dataUserCliente = false;
         
                if (!$permisos["SoloPropios"]) 
                    $dataUserCliente = $this->Clientes_model->ClienteUsuarioBool($cliente, $usuario);
                else 
                    $dataUserCliente = true;
 
                if ($dataUserCliente) { 
                    $dataPedido = $this->Pedidos_model->obtenerPedidosCliente($cliente);
                    
                    if (isset($dataPedido) && $dataPedido != FALSE) {
                        foreach ($dataPedido as $item) {
                            //Validacion si el pago está programado para hoy, mañana o pasado mañana
                            $hoy = date("Y-m-d");
                            $datetime = date("Y-m-d", strtotime($item["DiaCobro"]));
                            $datetime1 = date_create($datetime);
                            $datetime2 = date_create($hoy);
                            $interval = date_diff($datetime1, $datetime2);
                            $val = intval($interval->format('%R%a'));
                            if ($val == 0) {
                                $diaCobroPedido = "Hoy";
                            } else if ($val == -1) {
                                $diaCobroPedido = "Mañana";
                            } else if ($val == -2) {
                                $diaCobroPedido = "Pasado Mañana";
                            } else if ($val == -3) {
                                $diaCobroPedido = "En 3 días";
                            } else if ($val == -4) {
                                $diaCobroPedido = date("d/m/Y", strtotime($item["DiaCobro"]));
                            } else if ($val == -5) {
                                $diaCobroPedido = date("d/m/Y", strtotime($item["DiaCobro"]));
                            } else if ($val == 1) {
                                $diaCobroPedido = "Ayer";
                            } else if ($val == 2) {
                                $diaCobroPedido = "Anteayer";
                            } else if ($val >= 3 && $val <= 5) {
                                $diaCobroPedido = "Hace " . $val . " días.";
                            }

                            $i = intval($item['Codigo']);
                            $codCliente = $itemCliente["Codigo"];
                            $telefono = trim($itemCliente["Telefono1"] . "-" . $itemCliente["Telefono2"]);
                            if ($diaCobroPedido != "") {
                                $dataPagos = $this->Pagos_model->obtenerPagosPedido($i);
                                if (isset($dataPagos) && $dataPagos != FALSE) {
                                    $dataPagoPedido[$i] = $dataPagos;
                                } else {
                                    $p1 = array(
                                        "Codigo" => "0",
                                        "Cliente" => $item["Cliente"],
                                        "Pedido" => $item["Codigo"],
                                        "Cuota" => $item["NumCuotas"],
                                        "Pago" => "0",
                                        "FechaPago" => "-",
                                        "TotalPago" => $item["Valor"],
                                        "Observaciones" => "",
                                        "Habilitado" => 1,
                                        "UsuarioCreacion" => "ADMIN",
                                        "FechaCreacion" => "",
                                        "UsuarioModificacion" => "",
                                        "FechaModificacion" => ""
                                    );
                                    $p["0"] = $p1;
                                    $dataPagoPedido[$i] = $p;
                                }
                            }
                        }

                        foreach ($dataPedido as $item1) {
                            //var_dump($item1["PaginaFisica"]);
                            //echo "<br><br>";
                            $i = intval($item1['Codigo']);
                            if (array_key_exists($i, $dataPagoPedido)) {
                                $dataProductoPedido = $this->Pedidos_model->obtenerProductosPedidosAll($item1['Codigo']); 
                                //1873 //6061
                                $productosFacturados = "";
                                foreach ($dataProductoPedido as $producto) {
                                    $pos = strpos($productosFacturados, $producto["Nombre"]);
                                    
                                    if ($pos === false) {
                                        if ($productosFacturados != "") {
                                            $productosFacturados .= ", ";
                                        }
                                        $productosFacturados .= $producto["Nombre"];
                                    }   
                                } 
                                
                                $dataPagosP["Nombre"] = $itemCliente["Nombre"];
                                $direccion = $itemCliente["Dir"];
                                $direccion = ($itemCliente["Etapa"] != "") ? $direccion . " ET " . $itemCliente["Etapa"] : $direccion;
                                $direccion = ($itemCliente["Torre"] != "") ? $direccion . " TO " . $itemCliente["Torre"] : $direccion;
                                $direccion = ($itemCliente["Apartamento"] != "") ? $direccion . " AP " . $itemCliente["Apartamento"] : $direccion;
                                $direccion = ($itemCliente["Manzana"] != "") ? $direccion . " MZ " . $itemCliente["Manzana"] : $direccion;
                                $direccion = ($itemCliente["Interior"] != "") ? $direccion . " IN " . $itemCliente["Interior"] : $direccion;
                                $direccion = ($itemCliente["Casa"] != "") ? $direccion . " CA " . $itemCliente["Casa"] : $direccion;
                                $dataPagosP["Direccion"] = $direccion;
                                $barrio = $itemCliente["Barrio"];
                                $diaCobro = $item1["DiaCobro"];
                                $dataPagosP["Pedidos"] = $i;
                                $cuota = 0;
                                $abonado = 0;
                                $diaCobro = "2018-01-01 00:00:00";
                                $f2 = "2018-01-01 00:00:00";
                                $ultimoPago = 0;

                                if ($dataPagoPedido[$i][0]["Pago"] != 0) {
                                    foreach ($dataPagoPedido[$i] as $item) {
                                        $cuota++;
                                        $abonado = $abonado + $item["Pago"];
                                        $f1 = $item["FechaPago"];
                                        if ($f1 > $f2) {
                                            $f2 = $f1;
                                        }
                                    }
                                    $dataPagosP["diacobro"] = date("d/m/Y", strtotime($diaCobro));
                                    $dataPagosP["fechaUltimoPago"] = date("d/m/Y", strtotime($f2));
                                    foreach ($dataPagoPedido[$i] as $item) {
                                        if ($item["FechaPago"] == $f2) {
                                            if ($item["Pago"] > $ultimoPago) {
                                                $ultimoPago = $item["Pago"];
                                            }
                                        }
                                    }
                                    $dataPagosP["UltimoPago"] = $ultimoPago;
                                } else {
                                    $dataPagosP["diacobro"] = "-";
                                    $dataPagosP["fechaUltimoPago"] = "-";
                                    $dataPagosP["UltimoPago"] = "0";
                                }
                                $dataPagosP["cuota"] = $cuota;
                                $dataPagosP["abonado"] = $abonado;
                                $dataPagosP["valor"] = $item1["Valor"];
                                $dataPagosP["saldo"] = intval($item1["Valor"]) - intval($abonado);
                                $dataPagosP["DiaCobro"] = $diaCobroPedido;
                                $dataPagosP["telefono"] = $telefono;
                                $dataPagosP["barrio"] = $barrio; 
                                $dataPagosP["codCliente"] = $codCliente;
                                $dataPagosP["PaginaFisica"] = $item1["PaginaFisica"];
                                $dataPagosP["productos"] = $productosFacturados;

                                $datosPagos[$i] = $dataPagosP;
                            }
                        }
                    }
                }
            }
            
            $datosPagos = $this->valPagosGestion($datosPagos);
            //die();
            //print_r($dataPagoPedido);
            return $datosPagos;
        } else {
            $this->session->set_flashdata("error", "No se encontraron datos de Clientes.");
            return false;
        }
    }

    public function SearchPermissions()
    { 
        $permisos = [];  

        //Consultar Cliente
        $idPermiso = 5;
        $permisos["Consultar"] = validarPermisoAcciones($idPermiso);  
        //Cambio de Fecha de Cobro
        $idPermiso = 8;
        $permisos["CambioFecha"] = validarPermisoAcciones($idPermiso);  
        //Cambio de Tarifa
        $idPermiso = 9;
        $permisos["CambioTarifa"] = validarPermisoAcciones($idPermiso);  
        //Hacer Recibo
        $idPermiso = 10;
        $permisos["Generar"] = validarPermisoAcciones($idPermiso);  
        //Pagos Realizados del Cliente
        $idPermiso = 11;
        $permisos["Pagos"] = validarPermisoAcciones($idPermiso);  
        //Devolución del Cliente
        $idPermiso = 12;
        $permisos["Devolucion"] = validarPermisoAcciones($idPermiso);  
        //Ver solo las llamadas de los Clientes Propios
        $idPermiso = 107;
        $permisos["SoloPropios"] = validarPermisoAcciones($idPermiso);  
            
        return $permisos;
    }
    
    public function obtenerListadosClientesNoLlamadaCobro() {
        $dataClientes = $this->Clientes_model->obtenerNomClientesDir($this->config->item('cli_devol'), $this->config->item('cli_paz'));
        $usuario = $this->session->userdata('Codigo');
        $PerfilId = $this->session->userdata('PerfilId');        
        $permisos = $this->SearchPermissions();  

        if (isset($dataClientes) && $dataClientes != FALSE) {
            $d = array();
            $dataPagoPedido = array();
            $datosPagos = array();
            $dataPagosP = array();
            foreach ($dataClientes as $itemCliente) {
                $diaCobroPedido = "";
                $cliente = $itemCliente["Codigo"];
                $dataUserCliente = false; 

                if (!$permisos["SoloPropios"]) 
                    $dataUserCliente = $this->Clientes_model->ClienteUsuarioBool($itemCliente["Codigo"], $usuario);
                else 
                    $dataUserCliente = true;

                if ($dataUserCliente) {
                    $dataPedido = $this->Pedidos_model->obtenerPedidosCliente($cliente);
                    if (isset($dataPedido) && $dataPedido != FALSE) {
                        foreach ($dataPedido as $item) {
                            //Validacion si el pago está programado para hoy, mañana o pasado mañana
                            $hoy = date("Y-m-d");
                            $datetime = date("Y-m-d", strtotime($item["DiaCobro"]));
                            $datetime1 = date_create($datetime);
                            $datetime2 = date_create($hoy);
                            $interval = date_diff($datetime1, $datetime2);
                            $val = intval($interval->format('%R%a'));
                            if ($val == 1) {
                                $diaCobroPedido = "Ayer";
                            } else if ($val == 2) {
                                $diaCobroPedido = "Anteayer";
                            } else if ($val >= 3) {
                                if ($val % 30 == 0) {
                                    $d = $val / 30;
                                    $diaCobroPedido = "Hace " . $d . " meses.";
                                } else {
                                    $diaCobroPedido = "Hace " . $val . " días.";
                                }
                            }

                            $i = intval($item['Codigo']);
                            $codCliente = $itemCliente["Codigo"];
                            $telefono = trim($itemCliente["Telefono1"] . "-" . $itemCliente["Telefono2"]);
                            if ($diaCobroPedido != "") {
                                $dataPagos = $this->Pagos_model->obtenerPagosPedido($i);
                                if (isset($dataPagos) && $dataPagos != FALSE) {
                                    $dataPagoPedido[$i] = $dataPagos;
                                } else {
                                    $p1 = array(
                                        "Codigo" => "0",
                                        "Cliente" => $item["Cliente"],
                                        "Pedido" => $item["Codigo"],
                                        "Cuota" => $item["NumCuotas"],
                                        "Pago" => "0",
                                        "FechaPago" => "-",
                                        "TotalPago" => $item["Valor"],
                                        "Observaciones" => "",
                                        "Habilitado" => 1,
                                        "UsuarioCreacion" => "ADMIN",
                                        "FechaCreacion" => "",
                                        "UsuarioModificacion" => "",
                                        "FechaModificacion" => ""
                                    );
                                    $p["0"] = $p1;
                                    $dataPagoPedido[$i] = $p;
                                }
                            }
                        }

                        foreach ($dataPedido as $item1) {
                            $i = intval($item1['Codigo']);
                            if (array_key_exists($i, $dataPagoPedido)) {
                                $dataProductoPedido = $this->Pedidos_model->obtenerProductosPedidosAll('6061'); 
                                //'1873' //6061
                                $productosFacturados = "";
                                foreach ($dataProductoPedido as $producto) {
                                    $pos = strpos($productosFacturados, $producto["Nombre"]);
                                    
                                    if ($pos === false) {
                                        if ($productosFacturados != "") {
                                            $productosFacturados .= ", ";
                                        }
                                        $productosFacturados .= $producto["Nombre"];
                                    }   
                                } 

                                $dataPagosP["Nombre"] = $itemCliente["Nombre"];
                                $direccion = $itemCliente["Dir"];
                                $direccion = ($itemCliente["Etapa"] != "") ? $direccion . " ET " . $itemCliente["Etapa"] : $direccion;
                                $direccion = ($itemCliente["Torre"] != "") ? $direccion . " TO " . $itemCliente["Torre"] : $direccion;
                                $direccion = ($itemCliente["Apartamento"] != "") ? $direccion . " AP " . $itemCliente["Apartamento"] : $direccion;
                                $direccion = ($itemCliente["Manzana"] != "") ? $direccion . " MZ " . $itemCliente["Manzana"] : $direccion;
                                $direccion = ($itemCliente["Interior"] != "") ? $direccion . " IN " . $itemCliente["Interior"] : $direccion;
                                $direccion = ($itemCliente["Casa"] != "") ? $direccion . " CA " . $itemCliente["Casa"] : $direccion;
                                $dataPagosP["Direccion"] = $direccion;
                                $barrio = $itemCliente["Barrio"];
                                $diaCobro = $item1["DiaCobro"];
                                $dataPagosP["Pedidos"] = $i;
                                $cuota = 0;
                                $abonado = 0;
                                $diaCobro = "2018-01-01 00:00:00";
                                $f2 = "2018-01-01 00:00:00";
                                $ultimoPago = 0;

                                if ($dataPagoPedido[$i][0]["Pago"] != 0) {
                                    foreach ($dataPagoPedido[$i] as $item) {
                                        $cuota++;
                                        $abonado = $abonado + $item["Pago"];
                                        $f1 = $item["FechaPago"];
                                        if ($f1 > $f2) {
                                            $f2 = $f1;
                                        }
                                    }
                                    $dataPagosP["diacobro"] = date("d/m/Y", strtotime($diaCobro));
                                    $dataPagosP["fechaUltimoPago"] = date("d/m/Y", strtotime($f2));
                                    foreach ($dataPagoPedido[$i] as $item) {
                                        if ($item["FechaPago"] == $f2) {
                                            if ($item["Pago"] > $ultimoPago) {
                                                $ultimoPago = $item["Pago"];
                                            }
                                        }
                                    }
                                    $dataPagosP["UltimoPago"] = $ultimoPago;
                                } else {
                                    $dataPagosP["diacobro"] = "-";
                                    $dataPagosP["fechaUltimoPago"] = "-";
                                    $dataPagosP["UltimoPago"] = "0";
                                }
                                $dataPagosP["cuota"] = $cuota;
                                $dataPagosP["abonado"] = $abonado;
                                $dataPagosP["valor"] = $item1["Valor"];
                                $dataPagosP["saldo"] = intval($item1["Valor"]) - intval($abonado);
                                $dataPagosP["DiaCobro"] = $diaCobroPedido;
                                $dataPagosP["telefono"] = $telefono;
                                $dataPagosP["barrio"] = $barrio; 
                                $dataPagosP["codCliente"] = $codCliente;
                                $dataPagosP["PaginaFisica"] = $item1["PaginaFisica"];
                                $dataPagosP["productos"] = $productosFacturados;

                                $datosPagos[$i] = $dataPagosP;
                            }
                        }
                    }
                }
            }
            $datosPagos = $this->valPagosGestion($datosPagos);
            //print_r($dataPagoPedido);
            return $datosPagos;
        } else {
            $this->session->set_flashdata("error", "No se encontraron datos de Clientes.");
            return false;
        }
    }

    public function obtenerListadosClientesCobroJson() {         
        $data = $this->obtenerListadosClientesCobro(); 
        $arreglo["data"] = [];

        if (isset($data) && $data != FALSE) {
            $i = 0;
            foreach ($data as $item) {
                $btn1 = "";
                $btn2 = "";
                $btn3 = "";
                $btn4 = ""; 

                $idPermiso = 25;
                if (validarPermisoAcciones($idPermiso)) {
                    $btn1 = '<a href = "#ModalCall" data-toggle = "modal" title = "Reportar Llamada" onclick = "DatosModal(\'' . $item["Pedidos"] . '\', \'' . $item["codCliente"] . '\', \'' . $item["Nombre"] . '\', \'' . $item["productos"] . '\', \'' . $item["Direccion"] . '\', \'' . $item["telefono"] . '\', \'' . $item["barrio"] . '\', \'' . money_format("%.0n", $item["saldo"]) . '\');"><i class = "fa fa-phone" aria-hidden = "true" style = "padding:5px;"></i></a>';
                }
                $idPermiso = 26;
                if (validarPermisoAcciones($idPermiso)) {
                    $btn2 = '<a href = "' . base_url() . 'Cobradores/GestionHoy/' . $item["Pedidos"] . '/' . $item["codCliente"] . '/" title = "Gestión de Llamada (30 días)" target="_blank"><i class = "fa fa-list-ul" aria-hidden = "true" style = "padding:5px;"></i></a>';
                }                
                $idPermiso = 10;
                if (validarPermisoAcciones($idPermiso)) {
                    $btn3 = '<a href = "' . base_url() . 'Pagos/Generar/' . $item["codCliente"] . '/" target="_blank" title = "Pagar"><i class = "fa fa-motorcycle" aria-hidden = "true" style = "padding:5px;"></i></a>';
                }

                $idPermiso = 27;
                if (validarPermisoAcciones($idPermiso)) {
                    $btn4 = '<a href = "' . base_url() . 'Cobradores/GestionHis/' . $item["Pedidos"] . '/' . $item["codCliente"] . '/" title = "TODAS las Gestión de Llamada" target="_blank"><i class = "fa fa-history" aria-hidden = "true" style = "padding:5px;"></i></a>';
                }

                $arreglo["data"][$i] = array(
                    "Nombre" => $item["Nombre"],
                    "Direccion" => $item["Direccion"],
                    "telefono" => $item["telefono"],
                    "cuota" => $item["cuota"],
                    "saldo" => money_format("%.0n", $item["saldo"]),
                    "UltimoPago" => money_format("%.0n", $item["UltimoPago"]),
                    "DiaCobro" => $item["DiaCobro"],
                    "Ubicacion" => $item["PaginaFisica"],
                    "Motivo" => $item["Motivo"],
                    "btn" => '<div class="btn-group text-center" style="margin: 0px auto;  width:100%;">' . $btn1 . $btn2 . $btn3 . $btn4 . '</div>'
                );
                $i++;
            }
        }
        echo json_encode($arreglo);
    }

    public function obtenerListadosClientesNoLlamadaCobroJson() {
        $data = $this->obtenerListadosClientesNoLlamadaCobro();
        $arreglo["data"] = [];

        if (isset($data) && $data != FALSE) { 
            $i = 0;
            foreach ($data as $item) { 
                $btn1 = "";
                $btn2 = "";
                $btn3 = "";
                $btn4 = "";
                
                $idPermiso = 25;
                if (validarPermisoAcciones($idPermiso)) {
                    $btn1 = '<a href = "#ModalCall" data-toggle = "modal" title = "Reportar Llamada" onclick = "DatosModal(\'' . $item["Pedidos"] . '\', \'' . $item["codCliente"] . '\', \'' . $item["Nombre"] . '\', \'' . $item["productos"] . '\', \'' . $item["Direccion"] . '\', \'' . $item["telefono"] . '\', \'' . $item["barrio"] . '\', \'' . money_format("%.0n", $item["saldo"]) . '\');"><i class = "fa fa-phone" aria-hidden = "true" style = "padding:5px;"></i></a>';
                }  

                $idPermiso = 26;
                if (validarPermisoAcciones($idPermiso)) {
                    $btn2 = '<a href = "' . base_url() . 'Cobradores/GestionHoy/' . $item["Pedidos"] . '/' . $item["codCliente"] . '/" title = "Gestión de Llamada (30 días)" target="_blank"><i class = "fa fa-list-ul" aria-hidden = "true" style = "padding:5px;"></i></a>';
                }  

                $idPermiso = 10;
                if (validarPermisoAcciones($idPermiso)) {
                    $btn3 = '<a href = "' . base_url() . 'Pagos/Generar/' . $item["codCliente"] . '/" target="_blank" title = "Pagar"><i class = "fa fa-motorcycle" aria-hidden = "true" style = "padding:5px;"></i></a>';
                }  

                $idPermiso = 27;
                if (validarPermisoAcciones($idPermiso)) {
                    $btn4 = '<a href = "' . base_url() . 'Cobradores/GestionHis/' . $item["Pedidos"] . '/' . $item["codCliente"] . '/" title = "TODAS las Gestión de Llamada" target="_blank"><i class = "fa fa-history" aria-hidden = "true" style = "padding:5px;"></i></a>';
                }

                $arreglo["data"][$i] = array(
                    "Nombre" => $item["Nombre"],
                    "Direccion" => $item["Direccion"],
                    "telefono" => $item["telefono"],
                    "cuota" => $item["cuota"],
                    "saldo" => money_format("%.0n", $item["saldo"]),
                    "UltimoPago" => money_format("%.0n", $item["UltimoPago"]),
                    "DiaCobro" => $item["DiaCobro"],
                    "Ubicacion" => $item["PaginaFisica"],
                    "Motivo" => $item["Motivo"],
                    "btn" => '<div class="btn-group text-center" style="margin: 0px auto;  width:100%;">' . $btn1 . $btn2 . $btn3 . $btn4 . '</div>'
                );
                $i++;
            }
        }
        echo json_encode($arreglo);
    }

    public function Consultar($pago) {
        $idPermiso = 19;
        $page = validarPermisoPagina($idPermiso);

        $dataPago = $this->Pagos_model->obtenerPagosCod($pago);
        if (isset($dataPago) && $dataPago != FALSE) {
            $dataPedido = $this->Pedidos_model->obtenerProductosPedidoClienteAll($dataPago[0]["Pedido"]);
            if (isset($dataPedido) && $dataPedido != FALSE) {
                $dataClientes = $this->Clientes_model->obtenerClienteDir($dataPedido[0]["Cliente"]);
                if (isset($dataClientes) && $dataClientes != FALSE) {
                    $dataCobradores = $this->Cobradores_model->obtenerCobrador($dataPago[0]["Cobrador"]);
                    if ($dataCobradores == FALSE) {
                        $Cobradores = array(
                            "Codigo" => "1",
                            "Nombre" => "Sin Cobrador Asociado"
                        );
                        $dataCobradores [0] = $Cobradores;
                    }

                    $dataPagoPedido = array();
                    foreach ($dataPedido as $item) {
                        $i = intval($item['CodPedido']);
                        $dataPagos = $this->Pagos_model->obtenerPagosPedido($i);
                        if (isset($dataPagos) && $dataPagos != FALSE) {
                            $dataPagoPedido[$i] = $dataPagos;
                        }
                    }

                    $datosPagos = array();
                    $dataPagosP = array();
                    foreach ($dataPedido as $item1) {
                        $i = intval($item1['CodPedido']);
                        if (array_key_exists($i, $dataPagoPedido)) {
                            $dataPagosP["Pedidos"] = $i;
                            $cuota = 0;
                            $abonado = 0;
                            $f2 = "2018-01-01 00:00:00";
                            foreach ($dataPagoPedido[$i] as $item) {
                                $cuota ++;
                                $abonado = $abonado + $item["Pago"];
                                $f1 = $item["FechaPago"];
                                if ($f1 > $f2) {
                                    $f2 = $f1;
                                }
                            }
                            $dataPagosP["cuota"] = $cuota;
                            $dataPagosP["abonado"] = $abonado;
                            $dataPagosP["valor"] = $item1["Valor"];
                            $dataPagosP["saldo"] = intval($item1["Valor"]) - intval($abonado);
                            $dataPagosP["fechaUltimoPago"] = $f2;
                            $ultimoPago = 0;

                            foreach ($dataPagoPedido[$i] as $item) {
                                if ($item["FechaPago"] == $f2) {
                                    if ($item["Pago"] > $ultimoPago) {
                                        $ultimoPago = $item["Pago"];
                                    }
                                }
                            }
                            $dataPagosP["UltimoPago"] = $ultimoPago;
                            $datosPagos[$i] = $dataPagosP;
                        } else {
                            $dataPagosP["Pedidos"] = $i;
                            $dataPagosP["cuota"] = "0";
                            $dataPagosP["abonado"] = "0";
                            $dataPagosP["valor"] = $item1["Valor"];
                            $dataPagosP["saldo"] = intval($item1["Saldo"]);
                            $dataPagosP["fechaUltimoPago"] = "--";
                            $ultimoPago = 0;
                            $dataPagosP["UltimoPago"] = 0;
                            $datosPagos[$i] = $dataPagosP;
                        }
                    }

                    $data = new stdClass();
                    $data->Controller = "Pagos";
                    $data->title = "Información del Pago";
                    $data->subtitle = "Confirmar Pago";
                    $data->contenido = $this->viewControl . '/Consultar';
                    $data->cliente = $dataPedido[0]["Cliente"];
                    $data->pedido = $dataPedido[0]["CodPedido"];
                    $data->valor = $dataPedido[0]["Valor"];
                    $data->codigo = $pago;
                    $data->ListaDatos = $dataPedido;
                    $data->ListaDatos2 = $dataClientes;
                    $data->ListaDatos3 = $datosPagos;
                    $data->dataPago = $dataPago;
                    $data->Lista1 = $dataCobradores;

                    $this->load->view('frontend', $data);
                } else {
                    $this->session->set_flashdata("error", "No se encontraron datos del Cliente: <b>" . $dataPedido[0]["Cliente"] . "</b>");
                    redirect(base_url("/Clientes/Admin/"));
                }
            } else {
                $this->session->set_flashdata("error", "No se encontraron datos del Pedido del Pago: <b>" . $dataPago[0]["Pedido"] . "</b>");
                redirect(base_url("/Clientes/Admin/"));
            }
        } else {
            $this->session->set_flashdata("error", "No se encontraron datos del Pago");
            redirect(base_url("/Pagos/Admin/"));
        }
    }

    public function Log($pedido) {        
        $idPermiso = 13;
        $page = validarPermisoPagina($idPermiso);

        if ($pedido == null || $pedido == "") {
            $this->session->set_flashdata("error", "No se encontró Pedido.");
            redirect(base_url("/Clientes/Admin/"));
        } else {
            $dataPedido = $this->Pedidos_model->obtenerProductosPedidoClienteAll($pedido);
            if (isset($dataPedido) && $dataPedido != FALSE) {
                $cliente = $dataPedido[0]["Cliente"];
                $dataLog = $this->Pedidos_model->LogPedido($pedido);
                if (isset($dataLog) && $dataLog != FALSE) {
                    $datacliente = $this->Clientes_model->obtenerCliente($cliente);

                    $data = new stdClass();
                    $data->Controller = "Log";
                    $data->title = "Historial de Pagos";
                    $data->subtitle = "Pagos del Pedido <b>" . $pedido . "</b>";
                    $data->contenido = $this->viewControl . '/Log';
                    $data->cliente = $cliente;
                    $data->ListaDatos = $dataLog;

                    foreach ($dataLog as $value) {
                        //Obtener Codigo del Pago y del Recibo de Pago
                        $len = strlen($value["Datos"]);
                        $pos = (strpos($value["Datos"], "Codigo:") + 8);
                        $cod = intval(substr($value["Datos"], $pos, $len));
                        if ($value["Tabla"] === "Pagos") {
                            $dataP = $this->Pagos_model->obtenerPagosCod($cod);
                        } else if ($value["Tabla"] === "PagosProgramados") {
                            $dataP = $this->Pagos_model->obtenerPagosProgramaCod($cod);
                        }
                    }
                    if ($datacliente[0]["Estado"] == 123) {
                        $this->session->set_flashdata("msg", "El Cliente <b>" . $datacliente[0]["Nombre"] . "</b> pagó el total de la Biblia.");
                    }

                    $this->load->view('frontend', $data);
                } else {
                    $this->session->set_flashdata("error", "El Pedido no tiene Registros de Log");
                    redirect(base_url("/Clientes/Pagos/" . $cliente . "/"));
                }
            } else {
                $this->session->set_flashdata("error", "No se encontraron datos del Pedido: <b>" . $pedido . "</b>");
                redirect(base_url("/Clientes/Admin/"));
            }
        }
    }

    public function VerLog($codigo) {
        $dataLog = $this->Log_model->obtenerLogPorCod($codigo);
        if (isset($dataLog) && $dataLog != FALSE) {
            $data = new stdClass();
            $data->Controller = "Log";
            $data->title = "Log de Registros";
            $data->subtitle = "Registros número <b>" . $codigo . "</b>";
            $data->contenido = $this->viewControl . '/VerLog';
            $data->ListaDatos = $dataLog;
            $this->load->view('frontend', $data);
        } else {
            $this->session->set_flashdata("error", "El Registro <b>" . $codigo . "</b> no fue encontrado.");
            redirect(base_url("/Clientes/Admin/"));
        }
    }

    public function Historial($pedido) {
        $idPermiso = 20;
        $page = validarPermisoPagina($idPermiso);

        if ($pedido == null || $pedido == "") {
            $this->session->set_flashdata("error", "No se encontró Pedido.");
            redirect(base_url("/Clientes/Admin/"));
        } else {
            $dataPedido = $this->Pedidos_model->obtenerProductosPedidoClienteAll($pedido);
            if (isset($dataPedido) && $dataPedido != FALSE) {
                $cliente = $dataPedido[0]["Cliente"];
                $datacliente = $this->Clientes_model->obtenerCliente($cliente);

                $dataLog = $this->Pagos_model->obtenerHistorialPagosPedido($pedido);
                if (isset($dataLog) && $dataLog != FALSE) {
                    $data = new stdClass();
                    $data->Controller = "Log";
                    $data->title = "Historial de Pagos";
                    $data->subtitle = "Pagos del Pedido <b>" . $pedido . "</b>";
                    $data->contenido = $this->viewControl . '/Historial';
                    $data->ListaDatos = $dataLog;
                    $data->cliente = $cliente;
                    if ($datacliente[0]["Estado"] == 123) {
                        $this->session->set_flashdata("msg", "El Cliente <b>" . $datacliente[0]["Nombre"] . "</b> pagó el total de la Biblia.");
                    }

                    $this->load->view('frontend', $data);
                } else {
                    $this->session->set_flashdata("error", "No hay Historial de Pagos");
                    redirect(base_url("/Clientes/Pagos/" . $cliente . "/"));
                }
            } else {
                $this->session->set_flashdata("error", "No se encontraron datos del Pedido: <b>" . $pedido . "</b>");
                redirect(base_url("/Clientes/Admin/"));
            }
        }
    }

    public function History($cliente, $pedido, $fecha, $usuario, $accion, $saldoAnt, $cuota, $saldoNue, $abono, $obs) {
        $historia = array(
            "Pedido" => $pedido,
            "Cliente" => $cliente,
            "FechaHistorial" => $fecha,
            "Accion" => $accion,
            "SaldoAnterior" => $saldoAnt,
            "Cuota" => $cuota,
            "Abono" => $abono,
            "SaldoNuevo" => $saldoNue,
            "Observaciones" => $obs,
            "UsuarioCreacion" => $usuario,
            "FechaCreacion" => $fecha
        );
        $this->Pagos_model->saveHistoria($historia);
    }

    public function inhabilitarLlamadas($cliente, $pedido, $fecha, $usuario) {
        $llamadas = array(
            "Habilitado" => 0,
            "UsuarioModificacion" => $usuario,
            "FechaModificacion" => $fecha
        );
        $this->Pagos_model->inhabilitarLlamadas($cliente, $pedido, $llamadas);
    }

    public function Morosos() {
        redirect(site_url($this->viewControl . "/Buscar/"));
    }

    public function Datacredito() {
        redirect(site_url($this->viewControl . "/Buscar/"));
    }

    public function ReportarData($pedido) {
        if ($pedido != "") {
            $dataPedido = $this->Pedidos_model->obtenerPedido($pedido);
            if ($dataPedido != FALSE) {
                $estado = $dataPedido[0]["Estado"];
                //Se valida Estado 125 Datacrédito
                if ($estado == 125) {
                    //Datos Auditoría
                    $user = $this->session->userdata('Usuario');
                    $fecha = date("Y-m-d H:i:s");

                    $fecha1 = date("Y-m-d", strtotime($dataPedido[0]["DiaCobro"]));
                    $fecha2 = date("Y-m-d", strtotime($fecha));
                    $interval = date_diff(date_create($fecha1), date_create($fecha2));
                    $diferencia = intval($interval->format('%a'));
                    $signo = $interval->format('%R');
                    if ($diferencia >= 90 && $signo == "+") {
                        $observaciones = "Se Reporta a Datacrédito por falta de Pago después de " . $interval->format('%a') . " días.\n---\nEl Pago debió hacerse el día " . date("d/m/Y", strtotime($dataPedido[0]["DiaCobro"])) . ".\nSe envía carta de Cobro Prejuridico.";
                        $dataPed = array(
                            "Estado" => 127, //Reportado
                            "Observaciones" => $observaciones,
                            "UsuarioModificacion" => $user,
                            "FechaModificacion" => $fecha
                        );

                        $cliente = $dataPedido[0]["Cliente"];
                        $dataCli = array(
                            "Estado" => 126, //Reportado
                            "Observaciones" => $observaciones,
                            "UsuarioModificacion" => $user,
                            "FechaModificacion" => $fecha
                        );

                        try {
                            if ($this->Pedidos_model->update($pedido, $dataPed)) {
                                $datapedido = $this->Pedidos_model->obtenerPedido($pedido);
                                $modulo = "Reportar Pedido";
                                $tabla = "Pedidos";
                                $accion = "Cambio Estado Pedido";
                                $data = compararCambiosLog($datapedido, $dataPed);
                                //var_dump($data);
                                if (count($data) > 2) {
                                    $data['Codigo'] = $pedido;
                                    $llave = $pedido;
                                    $sql = LogSave($data, $modulo, $tabla, $accion, $llave);
                                }
                                if ($this->Clientes_model->update($cliente, $dataCli)) {
                                    $datacliente = $this->Clientes_model->obtenerCliente($cliente);
                                    $modulo = "Reportar Pedido";
                                    $tabla = "Clientes";
                                    $accion = "Cambio Estado Cliente";
                                    $data = compararCambiosLog($datacliente, $dataCli);
                                    //var_dump($data);
                                    if (count($data) > 2) {
                                        $data['Codigo'] = $cliente;
                                        $llave = $cliente;
                                        $sql = LogSave($data, $modulo, $tabla, $accion, $llave);
                                    }
                                    echo 1;
                                } else {
                                    $this->session->set_flashdata("error", "No se pudo Actualizar el Cliente.");
                                    redirect(base_url("/Pagos/Datacredito/"));
                                }
                            } else {
                                $this->session->set_flashdata("error", "No se pudo Actualizar el Pedido.");
                                redirect(base_url("/Pagos/Datacredito/"));
                            }
                        } catch (Exception $e) {
                            $this->session->set_flashdata("error", "Ha habido una excepción: " . $e->getMessage());
                            redirect(base_url("/Pagos/Datacredito/"));
                        }
                    } else {
                        $this->session->set_flashdata("error", "Los días del NO PAGO del Pedido " . $pedido . " no aplican para hacer el Reporte. " . $interval->format('%R%a') . " días.");
                        redirect(base_url("/Pagos/Datacredito/"));
                    }
                } else {
                    $this->session->set_flashdata("error", "El Pedido " . $pedido . " no está en Estado Datacrédito, por ende no puede hacer el Reporte");
                    redirect(base_url("/Pagos/Datacredito/"));
                }
            } else {
                $this->session->set_flashdata("error", "Debe indicar un Pedido válido para hacer el Reporte");
                redirect(base_url("/Pagos/Datacredito/"));
            }
        } else {
            $this->session->set_flashdata("error", "Debe indicar el Pedido del Cliente para hacer el Reporte");
            redirect(base_url("/Pagos/Datacredito/"));
        }
    }

    public function Revision($ReturnUrl = null) {
        $data = new stdClass();
        $data->Controller = "Pagos";
        $data->title = "Revisión de Clientes";
        $data->subtitle = "Revisión de Clientes";
        $data->contenido = $this->viewControl . '/Revision';

        $ReturnUrl = str_replace("%7C", "/", $ReturnUrl);
        $ReturnUrl = str_replace("|", "/", $ReturnUrl);

        $this->load->view('frontend', $data);
        $ret = Deuda();
        if ($ret == 1) {
            $this->session->set_flashdata("msg", "Se Actualizaron los estados de los Clientes.");
            redirect(base_url() . $ReturnUrl);
        } else {
            redirect(base_url() . $ReturnUrl);
        }
    }
 
    public function PagarMora($pedido) {
        $dataPedido = $this->Pedidos_model->obtenerPedidosClientePorPedido($pedido);
        if (isset($dataPedido) && $dataPedido != FALSE) {
            $dataClientes = $this->Clientes_model->obtenerClienteDir($dataPedido[0]["Cliente"]);
            if (isset($dataClientes) && $dataClientes != FALSE) {
                $cliente = $dataPedido[0]["Cliente"];
                $dataPagoPedido = array();
                foreach ($dataPedido as $item) {
                    $i = intval($item['Codigo']);
                    $dataPagos = $this->Pagos_model->obtenerPagosPedido($i);
                    if (isset($dataPagos) && $dataPagos != FALSE) {
                        $dataPagoPedido[$i] = $dataPagos;
                    }
                }

                $datosPagos = array();
                $dataPagosP = array();
                foreach ($dataPedido as $item1) {
                    $i = intval($item1['Codigo']);
                    if (array_key_exists($i, $dataPagoPedido)) {
                        $dataPagosP["Pedidos"] = $i;
                        $cuota = 0;
                        $abonado = 0;
                        $f2 = "2018-01-01 00:00:00";
                        foreach ($dataPagoPedido[$i] as $item) {
                            $cuota ++;
                            $abonado = $abonado + $item["Pago"];
                            $f1 = $item["FechaPago"];
                            if ($f1 > $f2) {
                                $f2 = $f1;
                            }
                        }
                        $dataPagosP["cuota"] = $cuota;
                        $dataPagosP["abonado"] = $abonado;
                        $dataPagosP["valor"] = $item1["Valor"];
                        $dataPagosP["saldo"] = intval($item1["Valor"]) - intval($abonado);
                        $dataPagosP["fechaUltimoPago"] = $f2;
                        $ultimoPago = 0;

                        foreach ($dataPagoPedido[$i] as $item) {
                            if ($item["FechaPago"] == $f2) {
                                if ($item["Pago"] > $ultimoPago) {
                                    $ultimoPago = $item["Pago"];
                                }
                            }
                        }
                        $dataPagosP["UltimoPago"] = $ultimoPago;
                        $dataSaldo = $this->calcularSaldoMinimo($item1["Valor"], $item1["DiaCobro"], $item1["ValCuota"]);
                        $dataPagosP["PagoMin"] = $dataSaldo["PagoMin"];
                        $dataPagosP["DiasDiferencia"] = $dataSaldo["DiasDiferencia"];

                        $datosPagos[$i] = $dataPagosP;
                    } else {
                        $dataPagosP["Pedidos"] = $i;
                        $dataPagosP["cuota"] = "0";
                        $dataPagosP["abonado"] = "0";
                        $dataPagosP["valor"] = $item1["Valor"];
                        $dataPagosP["saldo"] = intval($item1["Valor"]);
                        $dataPagosP["fechaUltimoPago"] = "--";
                        $ultimoPago = 0;
                        $dataPagosP["UltimoPago"] = 0;
                        $dataSaldo = $this->calcularSaldoMinimo($item1["Valor"], $item1["DiaCobro"], $item1["ValCuota"]);
                        $dataPagosP["PagoMin"] = $dataSaldo["PagoMin"];
                        $dataPagosP["DiasDiferencia"] = $dataSaldo["DiasDiferencia"];

                        $datosPagos[$i] = $dataPagosP;
                    }
                }



                $data = new stdClass();
                $data->Controller = "Pagos";
                $data->title = "Pagar Mora";
                $data->subtitle = "Programar Pago a Cliente";
                $data->contenido = $this->viewControl . '/PagarMora';
                $data->cliente = $cliente;
                $data->ListaDatos = $dataPedido;
                $data->ListaDatos2 = $dataClientes;
                $data->ListaDatos3 = $datosPagos;

                $this->load->view('frontend', $data);
            } else {
                $this->session->set_flashdata("error", "No se encontraron datos del Cliente: <b>$cliente</b>");
                redirect(base_url("/Clientes/Admin/"));
            }
        } else {
            $this->session->set_flashdata("error", "No se encontraron pedidos del Cliente: <b>$cliente</b>");
            redirect(base_url("/Clientes/Admin/"));
        }
    }

    public function calcularSaldoMinimo($valorTotal, $DiaCobro, $cuota) {
        $fechaPago = date("Y-m-d", strtotime($DiaCobro));
        $fecha = date("Y-m-d");
        $interval = date_diff(date_create($fechaPago), date_create($fecha));
        $diferencia = intval($interval->format('%a'));
        $signo = $interval->format('%R');
        if ($signo == "+") {
            $mensualidad = 0;
            $dias = 0;
            if ($diferencia > 30) {
                $mensualidad = intval($diferencia / 30);
                $dias = $diferencia - ($mensualidad * 30);
            }
            $pagodia = intval($cuota / 30) * $dias;
            $pagomes = intval($cuota * $mensualidad);
            $pagomin = $pagomes + $pagodia;
            $pagomin = ceil($pagomin / 1000) * 1000;
        } else {
            $pagomin = 0;
            $diferencia = 0;
        }
        $dataSaldo = array(
            "PagoMin" => $pagomin,
            "DiasDiferencia" => $diferencia
        );

        return $dataSaldo;
    }

    public function AdminProg() {
        $idPermiso = 28;
        $page = validarPermisoAcciones($idPermiso);

        $dataUsuarios = $this->Usuarios_model->obtenerUsuariosEP();
        $fecha = date("Y-m-d");
        $user = "*";
        //$num = $this->numPagosProgramados($user, date("Y-m-d 00:00:00"), date("Y-m-d 23:59:59"));
        $dataCobradores = $this->Cobradores_model->obtenerCobradores();
        if (isset($dataCobradores) && $dataCobradores != FALSE) {

            $data = new stdClass();
            $data->Controller = "Pagos";
            $data->title = "Recibos de Pago";
            $data->subtitle = "Recibos de Pago Filtro";
            $data->contenido = $this->viewControl . '/AdminProg';
            $data->ListaUsuarios = $dataUsuarios;
            $data->Lista1 = $dataCobradores;

            $this->load->view('frontend', $data);
        } else {
            $this->session->set_flashdata("error", "No se encontraron datos de los Cobradores, por favor intentelo de nuevo.");
            redirect(base_url("/Cobradores/Admin/"));
        }
    }

    public function numPagosProgramados($user = "*", $fechaIni = "", $fechaFin = "") {
        if ($user == "") {
            $user = "*";
        }
        if ($fechaIni == "") {
            $fechaIni = date('Y-m-d') . " 00:00:00";
        }
        if ($fechaFin == "") {
            $fechaFin = date('Y-m-d') . " 23:59:59";
        }
        try {
            $dataProgramados = $this->Pagos_model->obtenerPagosProgramaFechaUser($user, $fechaIni, $fechaFin);
            return count($dataProgramados);
        } catch (Exception $e) {
            return 'Error';
        }
    }

    public function pagosProgramados() {
        //Datos Auditoría 
        $fecha = date("Y-m-d H:i:s");

        $user = "*";
        $fechaIni = date('Y-m-d') . " 00:00:00";
        $fechaFin = date('Y-m-d') . " 23:59:59";
 
        try {
            $dataProgramados = $this->Pagos_model->obtenerPagosProgramaFechaUser($user, $fechaIni, $fechaFin);
            $arreglo["data"] = [];
            if (isset($dataProgramados) && $dataProgramados != FALSE) {
                $i = 0;
                foreach ($dataProgramados as $item) {
                    // var_dump($item);
                    // echo "<br><br>"; 
                    $pedidosClientes = $this->Pedidos_model->obtenerClientePorPedido($item["Pedido"]);
                    $btn1 = "<a href='" . base_url() . "Pagos/Validar/" . $item['Codigo'] . "/' target='_blank' title='Ver Recibo de Pago'><i class='fa fa-search' aria-hidden='true' style='padding:5px;'></i></a>";
                    $btn2 = "";
                    $btn3 = "";
                    $btn4 = "";

                    if ($item["NomEstado"] == "Programado") { 
                        $dia = date("d", strtotime($item["DiaCobro"] . "+ 1 month")); 
                        $mes = date("m", strtotime($fecha . "+ 1 month"));  
                        $anio = date("Y", strtotime($item["DiaCobro"] . "+ 1 month"));
  
                        $nuevoDiaCobro = date("d/m/Y", strtotime($dia . "-" . $mes . "-" . $anio));
                        echo "<br><br>"; 
                        $DiaCobro = date("d/m/Y", strtotime($item["DiaCobro"]));
                        //  echo "<br><br>"; 
                                                
                        $newline = '\n';
                        $item["Observaciones"] = str_replace("\n", $newline, $item["Observaciones"]);
                        $saldo = intval($pedidosClientes[0]["Saldo"]) - intval($item["Cuota"]);
                        $btn2 = html_entity_decode("<a href='#ModalConfirmarPago' data-toggle='modal' onclick='dataModalConfirmar(\"" . $item['Codigo'] . "\", \"" . money_format("%.0n", $item["Cuota"]) . "\", \"" . money_format("%.0n", $saldo) . "\", \"" . $item["Valor"] . "\", \"" . $pedidosClientes[0]['Cliente'] . "\", \"" . $item["Pedido"] . "\", \"" . $pedidosClientes[0]['Nombre'] . "\", \"" . $item["Observaciones"] . "\", \"" . $DiaCobro . "\", \"" . $nuevoDiaCobro . "\");' title='Confirmar Pago'><i class='fa fa-check' aria-hidden='true' style='padding:5px;'></i></a>");
                        $btn3 = html_entity_decode("<a href='#ModalDescartarPago' data-toggle='modal' onclick='dataModalDescartar(\"" . $item['Codigo'] . "\", \"" . $item["Pedido"] . "\", \"" . $pedidosClientes[0]['Cliente'] . "\", \"" . $pedidosClientes[0]['Nombre'] . "\", \"" . money_format("%.0n", $item["Cuota"]) . "\", \"" . money_format("%.0n", $pedidosClientes[0]["Saldo"]) . "\", \"" . $item["Valor"] . "\", \"" . $item["Observaciones"] . "\");' title='Descartar Pago'><i class='fa fa-close' aria-hidden='true' style='padding:5px;'></i></a>");
                        $f1 = strtotime($item["FechaImpresion"]);
                        $f2 = strtotime(date("d-m-Y 00:00:00", time()));

                        if ($item["Copias"] == 0) {
                            $btn4 = "<a href='#ModalPrintSolo' data-toggle='modal' title='Imprimir Recibo de Pago' onclick='dataModalSolo(\"1\",\"" . money_format("%.0n", $item["Cuota"]) . "\", \"" . $item['Codigo'] . "\", \"" . $item["Pedido"] . "\");'><i class='fa fa-print' aria-hidden='true' style='padding:5px;'></i></a>";
                        } else {
                            if (($item["Copias"] > 1 && $item["Copias"] < 3) && $f1 != $f2) {
                                $btn4 = "<a href='#ModalPrintSolo' data-toggle='modal' title='Imprimir Recibo de Pago' onclick='dataModalSolo(\"1\",\"" . money_format("%.0n", $item["Cuota"]) . "\", \"" . $item['Codigo'] . "\", \"" . $item["Pedido"] . "\");'><i class='fa fa-print' aria-hidden='true' style='padding:5px;'></i></a>";
                            }
                        }
                    }

                    if (strlen($item["Observaciones"]) > 30) {
                        $osb = substr($item["Observaciones"], 0, 30) . " (...)";
                    } else {
                        $osb = $item["Observaciones"];
                    }

                    $direccion = $pedidosClientes["0"]["Dir"];
                    $direccion = ($pedidosClientes["0"]["Etapa"] != "") ? $direccion . " ET " . $pedidosClientes["0"]["Etapa"] : $direccion;
                    $direccion = ($pedidosClientes["0"]["Torre"] != "") ? $direccion . " TO " . $pedidosClientes["0"]["Torre"] : $direccion;
                    $direccion = ($pedidosClientes["0"]["Apartamento"] != "") ? $direccion . " AP " . $pedidosClientes["0"]["Apartamento"] : $direccion;
                    $direccion = ($pedidosClientes["0"]["Manzana"] != "") ? $direccion . " MZ " . $pedidosClientes["0"]["Manzana"] : $direccion;
                    $direccion = ($pedidosClientes["0"]["Interior"] != "") ? $direccion . " IN " . $pedidosClientes["0"]["Interior"] : $direccion;
                    $direccion = ($pedidosClientes["0"]["Casa"] != "") ? $direccion . " CA " . $pedidosClientes["0"]["Casa"] : $direccion;
                    $direccion = $direccion . "-- Ubicación: " . $pedidosClientes["0"]["PaginaFisica"];
                    $telefono = $pedidosClientes["0"]["Telefono1"];
                    $telefono = ($pedidosClientes["0"]["Telefono2"] != "") ? $telefono . " - " . $pedidosClientes["0"]["Telefono2"] : $telefono;
                    $num = $this->Pagos_model->ultimaCuota($item["Pedido"]);
                    $numCuotas = $num[0]["Cuota"] + 1;

                    $arreglo["data"][$i] = array(
                        "pedido" => $pedidosClientes[0]["Nombre"],
                        "numCuota" => $numCuotas,
                        "saldo" => money_format("%.0n", $pedidosClientes["0"]["Saldo"]),
                        "cuota" => money_format("%.0n", $item["Cuota"]),
                        "fecha" => date("d/m/Y", strtotime($item["FechaProgramada"])),
                        "estado" => $item["NomEstado"],
                        "direccion" => $direccion,
                        "btn" => '<div class="btn-group text-center" style="margin: 0px auto;  width:100%;">' . $btn1 . $btn2 . $btn3 . $btn4 . '</div>'
                    );
                    $i++;
                }
            }
            echo json_encode($arreglo);
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage() . "<br>";
        }
    }

    public function FiltroProg() {
        $user = trim($this->input->post('pag_usu'));
        // $user = "*";
        $fechaIni = trim($this->input->post('pag_fec1'));
        // $fechaIni = date("Y-m-d");
        $date = str_replace('/', '-', $fechaIni);
        $fechaIni = date('Y-m-d', strtotime($date)) . " 00:00:00";
        $fechaFin = trim($this->input->post('pag_fec2'));
        // $fechaFin = date("Y-m-d");
        $date = str_replace('/', '-', $fechaFin);
        $fechaFin = date("Y-m-d", strtotime($date)) . " 23:59:59";
        
        // echo $user."<br>";
        // echo $fechaIni."<br>";
        // echo $fechaFin."<br>";

        try {
            $dataProgramados = $this->Pagos_model->obtenerPagosProgramaFechaUser($user, $fechaIni, $fechaFin);
            // var_dump($dataProgramados);
            // die();
            $arreglo["data"] = [];
            $totalPago = 0;
            if (isset($dataProgramados) && $dataProgramados != FALSE) {
                $i = 0;
                foreach ($dataProgramados as $item) {
                    $pedidosClientes = $this->Pedidos_model->obtenerClientePorPedido($item["Pedido"]);
                    $btn1 = "";
                    $btn2 = "";
                    $btn3 = "";
                    $btn4 = "";

                    $idPermiso = 15;
                    $accion = validarPermisoAcciones($idPermiso);
                    if ($accion) {
                        $btn1 = "<a href='" . base_url() . "Pagos/Validar/" . $item['Codigo'] . "/' target='_blank' title='Ver Recibo de Pago'><i class='fa fa-search' aria-hidden='true' style='padding:5px;'></i></a>";
                    }

                    if ($item["NomEstado"] == "Programado") {
                        $saldo = intval($pedidosClientes[0]["Saldo"]) - intval($item["Cuota"]);
                        $newline = '\n';
                        $item["Observaciones"] = str_replace("\n", $newline, $item["Observaciones"]);  

                        $proximoPago = $this->getNextDayPay($pedidosClientes[0]["DiaCobro"]);
                        $proximoPago = date("d/m/Y", strtotime($proximoPago));
                        
                        $idPermiso = 16;
                        $accion = validarPermisoAcciones($idPermiso);
                        if ($accion) {
                            $btn2 = html_entity_decode("<a href='#ModalConfirmarPago' data-toggle='modal' onclick='dataModalConfirmar(\"" . $item['Codigo'] . "\", \"" . money_format("%.0n", $item["Cuota"]) . "\", \"" . money_format("%.0n", $saldo) . "\", \"" . $item["Valor"] . "\", \"" . $pedidosClientes[0]['Cliente'] . "\", \"" . $item["Pedido"] . "\", \"" . $pedidosClientes[0]['Nombre'] . "\", \"" . $item["Observaciones"] . "\", \"" . $proximoPago . "\");' title='Confirmar Pago'><i class='fa fa-check' aria-hidden='true' style='padding:5px;'></i></a>");
                        }
                        $idPermiso = 17;
                        $accion = validarPermisoAcciones($idPermiso);
                        if ($accion) {
                            $btn3 = html_entity_decode("<a href='#ModalDescartarPago' data-toggle='modal' onclick='dataModalDescartar(\"" . $item['Codigo'] . "\", \"" . $item["Pedido"] . "\", \"" . $pedidosClientes[0]['Cliente'] . "\", \"" . $pedidosClientes[0]['Nombre'] . "\", \"" . money_format("%.0n", $item["Cuota"]) . "\", \"" . money_format("%.0n", $pedidosClientes[0]["Saldo"]) . "\", \"" . $item["Valor"] . "\", \"" . $item["Observaciones"] . "\");' title='Descartar Pago'><i class='fa fa-close' aria-hidden='true' style='padding:5px;'></i></a>");
                        }

                        $idPermiso = 18;
                        $accion = validarPermisoAcciones($idPermiso);
                        if ($accion) {
                            $f1 = strtotime($item["FechaImpresion"]);
                            $f2 = strtotime(date("d-m-Y 00:00:00", time()));

                            if ($item["Copias"] == 0) {
                                $btn4 = "<a href='#ModalPrintSolo' data-toggle='modal' title='Imprimir Recibo de Pago' onclick='dataModalSolo(\"1\",\"" . money_format("%.0n", $item["Cuota"]) . "\", \"" . $item['Codigo'] . "\", \"" . $item["Pedido"] . "\");'><i class='fa fa-print' aria-hidden='true' style='padding:5px;'></i></a>";
                            } else {
                                if (($item["Copias"] > 1 && $item["Copias"] < 3) && $f1 != $f2) {
                                    $btn4 = "<a href='#ModalPrintSolo' data-toggle='modal' title='Imprimir Recibo de Pago' onclick='dataModalSolo(\"1\",\"" . money_format("%.0n", $item["Cuota"]) . "\", \"" . $item['Codigo'] . "\", \"" . $item["Pedido"] . "\");'><i class='fa fa-print' aria-hidden='true' style='padding:5px;'></i></a>";
                                }
                            }
                        }
                    }

                    if (strlen($item["Observaciones"]) > 50) {
                        $osb = substr($item["Observaciones"], 0, 50) . " (...)";
                    } else {
                        $osb = $item["Observaciones"];
                    }

                    $pedidosClientes = $this->Pedidos_model->obtenerClientePorPedido($item["Pedido"]);
                    $direccion = $pedidosClientes["0"]["Dir"];
                    $direccion = ($pedidosClientes["0"]["Etapa"] != "") ? $direccion . " ET " . $pedidosClientes["0"]["Etapa"] : $direccion;
                    $direccion = ($pedidosClientes["0"]["Torre"] != "") ? $direccion . " TO " . $pedidosClientes["0"]["Torre"] : $direccion;
                    $direccion = ($pedidosClientes["0"]["Apartamento"] != "") ? $direccion . " AP " . $pedidosClientes["0"]["Apartamento"] : $direccion;
                    $direccion = ($pedidosClientes["0"]["Manzana"] != "") ? $direccion . " MZ " . $pedidosClientes["0"]["Manzana"] : $direccion;
                    $direccion = ($pedidosClientes["0"]["Interior"] != "") ? $direccion . " IN " . $pedidosClientes["0"]["Interior"] : $direccion;
                    $direccion = ($pedidosClientes["0"]["Casa"] != "") ? $direccion . " CA " . $pedidosClientes["0"]["Casa"] : $direccion;
                    $direccion = $direccion . "-- Ubicación: " . $pedidosClientes["0"]["PaginaFisica"];
                    $telefono = $pedidosClientes["0"]["Telefono1"];
                    $telefono = ($pedidosClientes["0"]["Telefono2"] != "") ? $telefono . " - " . $pedidosClientes["0"]["Telefono2"] : $telefono;
                    $num = $this->Pagos_model->ultimaCuota($item["Pedido"]);
                    $numCuotas = $num[0]["Cuota"] + 1;

                    $totalPago += $item["Cuota"];

                    $arreglo["data"][$i] = array(
                        "pedido" => $pedidosClientes[0]["Nombre"],
                        "numCuota" => $numCuotas,
                        "saldo" => money_format("%.0n", $pedidosClientes["0"]["Saldo"]),
                        "cuota" => money_format("%.0n", $item["Cuota"]),
                        "fecha" => date("d/m/Y", strtotime($item["FechaProgramada"])),
                        "estado" => $item["NomEstado"],
                        "direccion" => $direccion,
                        "btn" => '<div class="btn-group text-center" style="margin: 0px auto;  width:100%;">' . $btn1 . $btn2 . $btn3 . $btn4 . '</div>'
                    );
                    $i++;
                }
            }
            $arreglo["totalPago"] = $totalPago;
            echo json_encode($arreglo);
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage() . "<br>";
        }
    }

    public function CartaData($pedido) {
        $this->load->library('EdiPdf');
        // create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Ediciones Católicas');
        $pdf->SetTitle('Notificación DataCrédito');
        $pdf->SetSubject('Carta Prejuridico');

        // remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
            require_once(dirname(__FILE__) . '/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        // ---------------------------------------------------------
        // set font
        $pdf->SetFont('times', 'BI', 20);
            
        // add a page
        $pdf->AddPage();

        // set some text to print
        $txt = "Esta es la carta de notificación a datacrédito (Solicitar formato y texto)";

        // print a block of text using Write()
        $pdf->Write(0, $txt, '', 0, 'C', true, 0, false, false, 0);

        // ---------------------------------------------------------
        //Close and output PDF document
        $pdf->Output('Notificacion_DataCredito.pdf', 'I');
    }

    public function ImprimirRecibosPP() {
        $user = trim($this->input->post('pag_usu'));
        $fechaIni = trim($this->input->post('pag_fec1'));
        $date = str_replace('/', '-', $fechaIni);
        $fechaIni = date('Y-m-d', strtotime($date));
        $fechaFin = trim($this->input->post('pag_fec2'));
        $date = str_replace('/', '-', $fechaFin);
        $fechaFin = date("Y-m-d", strtotime($date));

        try {
            $dataProgramados = $this->Pagos_model->obtenerPagosProgramaFechaUser($user, $fechaIni, $fechaFin);
            if (isset($dataProgramados) && $dataProgramados != FALSE) {
                $this->session->set_flashdata("dataProgramados", $dataProgramados);
                echo 1;
            }
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage() . "<br>";
        }
    }

    public function PermisosImprimirRecibos() {  
        $idPermiso = 18;
        $page = validarPermisoAcciones($idPermiso);

        if ($page){
            echo 1;
        } else {
            echo 0;
        }
    }

    public function ImprimirReciboSolo($pedido, $pagoProg, $margen) { 
        $idPermiso = 18; 
        $page = validarPermisoAcciones($idPermiso);
        if ($page){
            if ($pedido != null && $pedido != "") {
                if ($pedido != null && $pedido != "") {
                    $dataProgramados = $this->Pagos_model->obtenerPagosProgramaPedidoProg($pedido, $pagoProg);
                    if (isset($dataProgramados) && $dataProgramados != FALSE) {
                        $p = $this->Pedidos_model->obtenerPedido($pedido);
                        $pagina = $p[0]["PaginaFisica"];

                        //$copias = $this->agregarCopia($dataProgramados[0]["Codigo"]);
                        $copias = true;
                        echo $htmlEncabezado = '<!doctype html>
                                <html lang="es-co">
                                        <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                                            
                                            <meta name="viewport" content="width=device-width,initial-scale=1">
                                            <title>Recibos de Pago - </title>
                                            <link rel="icon" type="image/png" href="' . base_url() . 'Public/images/logo/01.png"/>
                                        </head>
                                        <body>'; 
                        if ($copias != FALSE) {
                            $num = $this->Pagos_model->ultimaCuota($pedido);
                            $numCuotas = $num[0]["Cuota"] + 1;
                            $dataProgramados[0]["numCuotas"] = $numCuotas;
                            $dataCliente = $this->Clientes_model->obtenerClienteDir($dataProgramados[0]["Cliente"]);
                            $user = $this->session->userdata('Usuario');
                            $dataAdmin = $this->Usuarios_model->obtenerAdminPorUsuario($user);

                            $sub = $this->config->item('subdominio');
                            if ($sub == "Plataforma") {
                                $html = $this->RecibosSoloInfoJose($dataProgramados[0], $dataCliente[0], $dataAdmin, $pagina, $margen);
                            } elseif ($sub == "nelson") {
                                $html = $this->RecibosSoloInfoNelson($dataProgramados[0], $dataCliente[0], $dataAdmin, $pagina, $margen);
                            } elseif ($sub == "jose" || $sub == "n") {
                                //echo '<script>alert("No tiene un formato configurado. Por favor comuniquese con su administrador");  window.open("","_parent",""); window.close(); </script>';
                                $html = $this->RecibosSoloInfoJose($dataProgramados[0], $dataCliente[0], $dataAdmin, $pagina, $margen);
                            } elseif ($sub == "mundoktolico") {
                                $html = $this->RecibosSoloInfoMundoKtolico($dataProgramados[0], $dataCliente[0], $dataAdmin, $pagina, $margen);
                            } else {
                                echo '<script>alert("No tiene un formato configurado. Por favor comuniquese con su administrador");  window.open("","_parent",""); window.close(); </script>';
                                //$html = $this->TablaRecibo1($dataProgramados[0], $dataCliente[0], $dataAdmin, $pagina, $margen);
                            }
                            echo $html;
                        } else {
                            echo '<script>alert("Ya se imprimió el este recibo el día de hoy. Si desea reimprimirlos, debe comunicarse con el Administrador.");  window.open("","_parent",""); window.opener.location.reload(false); window.close(); </script>';
                        }
                        echo $htmlPie = '</body>
                                    </html>';
                    } else {
                        redirect(base_url("/Pagos/AdminProg/"));
                    }
                } else {
                    redirect(base_url("/Pagos/AdminProg/"));
                }
            } else {
                redirect(base_url("/Pagos/AdminProg/"));
            }
        } else {
            echo "<script>window.close();</script>";
        }
    }

    public function ImprimirRecibos($margen) {
        $idPermiso = 18; 
        $page = validarPermisoAcciones($idPermiso);
        $data = $this->session->flashdata("dataProgramados");
        if ($data != null) {
            $i = 0;
            echo $htmlEncabezado = '<!doctype html>
                    <html lang="es-co">
                            <head>
                                
                                <meta name="viewport" content="width=device-width,initial-scale=1">
                                <title>Recibos de Pago - </title>
                                <link rel="icon" type="image/png" href="' . base_url() . 'Public/images/logo/01.png"/>
                            </head>
                            <body>'; 
            $numRecibos = 0; 
            foreach ($data as $value) {
                //$copias = $this->agregarCopia($value["Codigo"]);
                $copias = true;
                if ($copias != FALSE) {
                    if ($numRecibos > 0) { 
                        if ($sub == "Plataforma") { 
                            echo "<p style='margin-bottom: -8px;'>.</p><br><br><br>";
                        } elseif ($sub == "nelson") {
                            //echo "<p style='margin-bottom: -10px;'>.</p><br><br><br>";
                            echo "";
                        } elseif ($sub == "mundoktolico") {
                            echo "<p style='margin-bottom: -5px;'>.</p><br><br><br>";
                        } elseif ($sub == "jose") {
                            echo "<p style='margin-bottom: -5px;'>.</p><br><br><br>";
                        }
                    }
                    $numRecibos ++;
                    $pagina = $data[$i]["Pagina"];

                    $num = $this->Pagos_model->ultimaCuota($value["Pedido"]);
                    $numCuotas = $num[0]["Cuota"] + 1;

                    $data[$i]["numCuotas"] = $numCuotas;
                    $dataCliente = $this->Clientes_model->obtenerClienteDir($value["Cliente"]);
                    $user = $this->session->userdata('Usuario');
                    $dataAdmin = $this->Usuarios_model->obtenerAdminPorUsuario($user);

                    $sub = $this->config->item('subdominio');
                    $html = "";
                    if ($sub == "Plataforma") {
                        $html = $this->RecibosSoloInfoJose($data[$i], $dataCliente[0], $dataAdmin, $pagina, $margen);
                    } elseif ($sub == "nelson") {
                        $html = $this->RecibosSoloInfoNelson($data[$i], $dataCliente[0], $dataAdmin, $pagina, $margen);
                    } elseif ($sub == "jose") {
                        //echo '<script>alert("No tiene un formato configurado. Por favor comuniquese con su administrador");  window.open("","_parent",""); window.close(); </script>';
                        $html = $this->RecibosSoloInfoJose($data[$i], $dataCliente[0], $dataAdmin, $pagina, $margen);
                    } elseif ($sub == "mundoktolico") {
                        $html = $this->RecibosSoloInfoMundoKtolico($data[$i], $dataCliente[0], $dataAdmin, $pagina, $margen);
                    } else {
                        echo '<script>alert("No tiene un formato configurado. Por favor comuniquese con su administrador");  window.open("","_parent",""); window.close(); </script>';
                        //$html = $this->TablaRecibo1($data[$i], $dataCliente[0], $dataAdmin);
                    }
                    echo $html;

                    $i++;
                } 
            }
            if ($numRecibos <= 0) {
                echo '<script>alert("Ya se imprimieron los recibos del día. Si desea reimprimirlos, debe comunicarse con el Administrador.");  window.open("","_parent",""); window.close(); </script>';
            }
            echo $htmlPie = '</body>
                    </html>';
        } else {
            redirect(base_url("/Pagos/AdminProg/"));
        }
    }

    public function agregarCopia($codigo) {
        if ($codigo != "") {
            $PagoPro = $this->Pagos_model->numCopias($codigo);
            if ($PagoPro != FALSE) {
                $num = $PagoPro[0]["Copias"];
                if ($num < 3) {
                    $f1 = strtotime($PagoPro[0]["FechaImpresion"]);
                    $f2 = strtotime(date("d-m-Y 00:00:00", time()));

                    if ($f1 != $f2) {
                        $this->addCopia($codigo, $num);
                        return true;
                    } else {
                        $perfil = $this->session->userdata('PerfilId');
                        $coordi = $this->session->userdata('Coordi');
                        if ($perfil == $coordi) {
                            $this->addCopia($codigo, $num);
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function addCopia($codigo, $num) {
        //Datos Auditoría
        $user = $this->session->userdata('Usuario');
        $fecha = date("Y-m-d H:i:s");
        $fechaImpresion = date("Y-m-d");

        $dataPagoPro = array(
            "Copias" => $num + 1,
            "FechaImpresion" => $fechaImpresion,
            "UsuarioModificacion" => $user,
            "FechaModificacion" => $fecha
        );

        if ($this->Pagos_model->updateProg($codigo, $dataPagoPro)) {
            $pago = $this->Pagos_model->obtenerPagosProgramaCod($codigo);
            $modulo = "Pagar Pedido";
            $tabla = "PagosProgramados";
            $accion = "Imprimir Recibo de Pago";
            $data = compararCambiosLog($pago, $dataPagoPro);
            //var_dump($data);
            if (count($data) > 2) {
                $data['Codigo'] = $codigo;
                $llave = $codigo;
                $sql = LogSave($data, $modulo, $tabla, $accion, $llave);
            }
        }
    }

     public function RecibosSoloInfoNelson($data, $cliente, $dataAdmin, $pagina, $margen) {
        setlocale(LC_ALL, "es_CO");
        $numeroRecibo = $data["Codigo"];
        $numeroRecibo = str_pad($numeroRecibo, 6, "0", STR_PAD_LEFT);
        $fecha = strftime("%d de %B del %Y", strtotime($data["FechaProgramada"])) . ' al ' . strftime("%d de %B del %Y", strtotime($data["FechaProgramada"] . "+ 8 days"));
        $nombreCliente = $cliente["Nombre"];
        $posSeparador = strpos($nombreCliente, '//');
        if ($posSeparador != FALSE){
            $nombreCliente = substr($nombreCliente, 0, $posSeparador);
        }

        $direccionCliente = $cliente["Dir"] . " Bar: " . $cliente["Barrio"];
        $etapa = $cliente["Etapa"];
        $torre = $cliente["Torre"];
        $apartamento = $cliente["Apartamento"];
        $manzana = $cliente["Manzana"];
        $interior = $cliente["Interior"];
        $casa = $cliente["Casa"];


        $valor = money_format("%.0n", $data["Valor"]);
        $abono = money_format("%.0n", $data["Cuota"]);
        $saldo = money_format("%.0n", $data["Saldo"] - $data["Cuota"]);
        $cuota = $data["numCuotas"];
        $observaciones = $data["Observaciones"];

        if ($margen == 1) {
            $margen = "margin-top: -3px;";
        } else if ($margen == 2) {
            $margen = "margin-top: -7px;";
        } else if ($margen == 3) {
            $margen = "margin-top: 3px;";
        } else if ($margen == 4) {
            $margen = "margin-top: 5px;";
        } else {
            $margen = "";
        }

         
                
                
            $html = "
            <style>
                * {
                    color:#000;
                }
                p {
                    margin:0px;
                    padding:0px;
                }
                .letraPeque{
                    min-height: 40px; 
                    max-height: 40px; 
                    font-size: 13px; 
                    line-height: 80%;
                }
                @media print{
                   div.recibo{ 
                      page-break-before:always;
                   }
                }
            </style>
            <div class='recibo' style='width: 490px; margin-top: 0px; border: 0px solid;" . $margen . "'>
                <p style='margin: 20px 0 -10px; text-align: right; font-weight: bold;'>" . $pagina . "</p>
                <br />
                <br />
                <p style='margin-left: 400px;color: red;'>" . $numeroRecibo . "</p>
                <br />
                <br />
                <p style='margin-left: 110px; margin-top: -15px; '>" . $fecha . "</p>
                <p style='margin-left: 125px; margin-top: 5px; '>" . $nombreCliente . "</p>
                <p style='margin-left: 125px; margin-top: 0px; width: 215px; display: block; float: left;' class='letraPeque'>" . $direccionCliente . "</p>
                <p style='margin-left: 30px; margin-top: 0px; width: 115px; display: block; float: left;' class='letraPeque'>" . $cliente["Telefono1"] . ' - ' . $cliente["Telefono2"] . "</p>
                <br />
                <br />
                <br />
                <div style='display: block; margin: 10px 0px 0px;'>
                    <label style='margin-left: 30px;'>" . $etapa . "</label>
                    <label style='margin-left: 60px;'>" . $torre . "</label>
                    <label style='margin-left: 60px;'>" . $apartamento . "</label>
                    <label style='margin-left: 75px;'>" . $manzana . "</label>
                    <label style='margin-left: 75px;'>" . $interior . "</label>
                    <label style='margin-left: 60px;'>" . $casa . "</label>
                </div>
                <p style='margin-left: 100px; display: block; float: left;'>" . $valor . "</p>
                <br>
                <p style='margin-left: 100px; display: block; float: left;'>" . $abono . "</p>
                <p style='margin-left: 220px; display: block; float: left;'>" . $cuota . "</p>
                <br>
                <p style='margin-left: 100px; display: block; float: left;'>" . $saldo . "</p>
                <br>
                <br>
                <p style='margin-left: 30px; display: block; margin-top: 0px; width: auto; line-height: 100%;' class='letraPeque'>" . $observaciones . "</p>
            </div>
            ";    
             
            
            $html1 = "
            <style>
                * {
                    color:#000;
                }
                p {
                    margin:0px;
                    padding:0px;
                }
                .letraPeque{
                    min-height: 40px; 
                    max-height: 40px; 
                    font-size: 13px; 
                    line-height: 80%;
                }
                .recibo {
                  width: 490px; 
                  border: 1px solid transparent; 
                  margin: -5px 10px 30px 0px; 
                  height: 380px; 
                  box-sizing: border-box; 
                }
            </style>
            <div class='recibo'>
                <p style='margin: 20px 0 -10px; text-align: right; font-weight: bold;'>" . $pagina . "</p>
                <br />
                <br />
                <p style='margin-left: 400px;color: red;'>" . $numeroRecibo . "</p>
                <br />
                <br />
                <p style='margin-left: 110px; margin-top: -15px; '>" . $fecha . "</p>
                <p style='margin-left: 125px; margin-top: 5px; '>" . $nombreCliente . "</p>
                <p style='margin-left: 125px; margin-top: 0px; width: 215px; display: block; float: left;' class='letraPeque'>" . $direccionCliente . "</p>
                <p style='margin-left: 30px; margin-top: 0px; width: 115px; display: block; float: left;' class='letraPeque'>" . $cliente["Telefono1"] . ' - ' . $cliente["Telefono2"] . "</p>
                <br />
                <br />
                <br />
                <div style='display: block; margin: 10px 0px 0px;'>
                  <label style='margin-left: 30px;'>" . $etapa . "</label>
                  <label style='margin-left: 60px;'>" . $torre . "</label>
                  <label style='margin-left: 60px;'>" . $apartamento . "</label>
                  <label style='margin-left: 75px;'>" . $manzana . "</label>
                  <label style='margin-left: 75px;'>" . $interior . "</label>
                  <label style='margin-left: 60px;'>" . $casa . "</label>
                </div>
                <p style='margin-left: 100px; display: block; float: left;'>" . $valor . "</p>
                <br>
                <p style='margin-left: 100px; display: block; float: left;'>" . $abono . "</p>
                <p style='margin-left: 220px; display: block; float: left;'>" . $cuota . "</p>
                <br>
                <p style='margin-left: 100px; display: block; float: left;'>" . $saldo . "</p>
                <br>
                <br>
                <p style='margin-left: 30px; display: block; margin-top: 0px; width: auto; line-height: 100%;' class='letraPeque'>" . $observaciones . "</p>
            </div> 
            ";
            
            $html2 = "
            <style>
                * {
                    color:#000;
                }
                p {
                    margin:0px;
                    padding:0px;
                }
                .letraPeque{
                    min-height: 40px; 
                    max-height: 40px; 
                    font-size: 13px; 
                    line-height: 80%;
                }
                .recibo {  
                    width: 490px;
                    margin-top: 0px;
                    border: 1px solid transparent; 
                    height: 10.5cm;
                    max-height: 10.5cm;  
                    box-sizing: border-box;
                }
                @media print{
                   div.recibo-container{ 
                      page-break-before:always;
                   }
                }
            </style> 
            <div class='recibo-container' style='display:flex;'>
            <div class='recibo' style='" . $margen . "'>
                <p style='margin: 20px 0 -10px; text-align: right; font-weight: bold;'>" . $pagina . "</p>
                <br />
                <br />
                <p style='margin-left: 400px;color: red;'>" . $numeroRecibo . "</p>
                <br />
                <br />
                <p style='margin-left: 110px; margin-top: -15px; '>" . $fecha . "</p>
                <p style='margin-left: 125px; margin-top: 5px; '>" . $nombreCliente . "</p>
                <p style='margin-left: 125px; margin-top: 0px; width: 215px; height: 40px; max-height: 40px;   display: block; float: left;' class='letraPeque'>" . $direccionCliente . "</p>
                <p style='margin-left: 30px; margin-top: 0px; width: 115px; display: block; float: left;' class='letraPeque'>" . $cliente["Telefono1"] . ' - ' . $cliente["Telefono2"] . "</p>
                <br />
                <br />
                <br />
                <div style='display: block; margin: 10px 0px 0px;'>
                    <label style='margin-left: 30px;'>" . $etapa . "</label>
                    <label style='margin-left: 60px;'>" . $torre . "</label>
                    <label style='margin-left: 60px;'>" . $apartamento . "</label>
                    <label style='margin-left: 75px;'>" . $manzana . "</label>
                    <label style='margin-left: 75px;'>" . $interior . "</label>
                    <label style='margin-left: 60px;'>" . $casa . "</label>
                </div>
                <p style='margin-left: 100px; display: block; float: left;'>" . $valor . "</p>
                <br>
                <p style='margin-left: 100px; display: block; float: left;'>" . $abono . "</p>
                <p style='margin-left: 220px; display: block; float: left;'>" . $cuota . "</p>
                <br>
                <p style='margin-left: 100px; display: block; float: left;'>" . $saldo . "</p>
                <br>
                <br>
                <p style='margin-left: 30px; display: block; margin-top: 0px; width: auto; line-height: 100%; height: 80px !important;' class='letraPeque'>" . $observaciones . "</p>
            </div> 
            </div>
            ";

        return $html;
    }

    public function RecibosSoloInfoMundoKtolico($data, $cliente, $dataAdmin, $pagina, $margen) {
        setlocale(LC_ALL, "es_CO");
        $numeroRecibo = $data["Codigo"];
        $numeroRecibo = str_pad($numeroRecibo, 6, "0", STR_PAD_LEFT);
        $fecha = strftime("%d de %B del %Y", strtotime($data["FechaProgramada"])) . ' al ' . strftime("%d de %B del %Y", strtotime($data["FechaProgramada"] . "+ 8 days"));
        $nombreCliente = $cliente["Nombre"];
        $direccionCliente = $cliente["Dir"] . " Bar: " . $cliente["Barrio"];
        $etapa = $cliente["Etapa"];
        $torre = $cliente["Torre"];
        $apartamento = $cliente["Apartamento"];
        $manzana = $cliente["Manzana"];
        $interior = $cliente["Interior"];
        $casa = $cliente["Casa"];


        $valor = money_format("%.0n", $data["Valor"]);
        $abono = money_format("%.0n", $data["Cuota"]);
        $saldo = money_format("%.0n", $data["Saldo"] - $data["Cuota"]);
        $cuota = $data["numCuotas"];
        $observaciones = $data["Observaciones"];

        if ($margen == 1) {
            $margen = "margin-top: -3px;";
        } else if ($margen == 2) {
            $margen = "margin-top: -5px;";
        } else if ($margen == 3) {
            $margen = "margin-top: 3px;";
        } else if ($margen == 4) {
            $margen = "margin-top: 5px;";
        } else {
            $margen = "";
        }

        $html = "
            <style>
                * {
                    color:#000;
                }
                p {
                    margin:0px;
                    padding:0px;
                }
                .letraPeque{
                    min-height: 40px; 
                    max-height: 40px; 
                    font-size: 13px; 
                    line-height: 80%;
                }
            </style>
            <br />
            <br />
            <br />
            <div style='width: 490px;" . $margen . "'>
                <br />
                <br />
                <br />
                <p style='margin-left: 110px; margin-top: -10px; '>" . $fecha . "</p>
                <p style='margin-left: 125px; margin-top: 2px; '>" . $nombreCliente . "</p>
                <p style='margin-left: 125px; margin-top: 1px; width: 330px; display: block; float: left;' class='letraPeque'>" . $direccionCliente . "</p>
                <br />
                <br />
                <div style='display: block; margin: 13px 0px 0px;'>
                    <label style='margin-left: 18px;'>" . $etapa . "</label>
                    <label style='margin-left: 3px;'>" . $torre . "</label>
                    <label style='margin-left: 60px;'>" . $apartamento . "</label>
                    <label style='margin-left: 70px;'>" . $manzana . "</label>
                    <label style='margin-left: 70px;'>" . $interior . "</label>
                    <label style='margin-left: 55px;'>" . $casa . "</label>
                </div>
                <p style='margin-left: 100px; margin-top: 0px; width: 190px; display: block; float: left;'>" . $valor . "</p>
                <p style='margin-left: 30px; margin-top: 0px; width: 115px; display: block; float: left;'>" . $abono . "</p>
                <br>
                <p style='margin-left: 100px; display: block; float: left;'>" . $saldo . "</p>
                <br>
                <br>
                <br>
                <p style='margin-left: 30px; display: block; margin-top: -7px; width: auto;' class='letraPeque'>" . $observaciones . "</p>
                <p style='margin-left: 30px; margin-top: 0px; width: 315px; display: block; float: left;'>" . $cliente["Telefono1"] . ' - ' . $cliente["Telefono2"] . "</p>                              
            </div>
            ";

        return $html;
    }

    public function RecibosSoloInfoJose($data, $cliente, $dataAdmin, $pagina, $margen) {
        setlocale(LC_ALL, "es_CO");
        $numeroRecibo = $data["Codigo"];
        $numeroRecibo = str_pad($numeroRecibo, 6, "0", STR_PAD_LEFT);
        $fecha = strftime("%d de %B del %Y", strtotime($data["FechaProgramada"])) . ' al ' . strftime("%d de %B del %Y", strtotime($data["FechaProgramada"] . "+ 8 days"));
        $nombreCliente = $cliente["Nombre"];
        $direccionCliente = $cliente["Dir"];
        $barrio = $cliente["Barrio"]; 
        
        $etapa = $cliente["Etapa"];    
        $torre = $cliente["Torre"];
        $apartamento = $cliente["Apartamento"];
        $manzana = $cliente["Manzana"];
        $interior = $cliente["Interior"];
        $casa = $cliente["Casa"];


        $valor = money_format("%.0n", $data["Valor"]);
        $abono = money_format("%.0n", $data["Cuota"]);
        $saldo = money_format("%.0n", $data["Saldo"] - $data["Cuota"]);
        $cuota = $data["numCuotas"];
        $observaciones = $data["Observaciones"]; 

        if ($margen == 1) {
            $margen = "margin-top: 16px;";
        } elseif ($margen == 1) {
            $margen = "margin-top: 18px;";
        } else if ($margen == 2) {
            $margen = "margin-top: 20px;";
        } else if ($margen == 3) {
            $margen = "margin-top: 13px;";
        } else if ($margen == 4) {
            $margen = "margin-top: 10px;";
        } else {
            $margen = "margin-top: 16px;";
        }
        
        $html = "
            <style>
                * {
                    color:#000;
                }
                p {
                    margin:0px;
                    padding:0px;
                }
                .letraPeque{
                    min-height: 40px; 
                    max-height: 40px; 
                    font-size: 13px; 
                    line-height: 80%;
                }
            </style>
            <br />
            <br />
            <br />
            <div style='width: 530px;" . $margen . "'>
                <br />
                <br />
                <br />
                <p style='margin-left: 110px; margin-top: -2px; '>" . $fecha . "</p>
                <p style='margin-left: 135px; margin-top: 4px; font-size: 18px; font-weight: bold;'>" . $nombreCliente . "</p>
                <p style='margin-left: 135px; margin-top: 2px; width: 330px; display: block; float: left; font-size: 18px; font-weight: bold;'>" . $direccionCliente . "</p>
                <br />
                <br />
                <div style='display: block; margin: 22px 5px 0px;'>
                    <div style='display: -webkit-inline-box; margin-left: 35px; min-width: 37px;'>
                        <label>" . $etapa . "</label>
                    </div>
                    <div style='display: -webkit-inline-box; margin-left: 35px; min-width: 35px;'>
                        <label>" . $torre . "</label>
                    </div>
                    <div style='display: -webkit-inline-box; margin-left: 37px; min-width: 43px;'>
                        <label>" . $apartamento . "</label>
                    </div>
                    <div style='display: -webkit-inline-box; margin-left: 35px; min-width: 59px;'>
                        <label>" . $manzana . "</label>
                    </div>
                    <div style='display: -webkit-inline-box; margin-left: 45px; min-width: 15px;'>
                        <label>" . $interior . "</label>
                    </div>
                    <div style='display: -webkit-inline-box; margin-left: 50px;'>
                        <label>" . $casa . "</label>
                    </div>
                </div>
                <p style='margin-left: 100px; margin-top: 6px; width: 160px; display: block; float: left;'>" . $valor . "</p>
                <p style='margin-left: 0px; margin-top: 6px; width: 115px; display: block; float: left;'>" . $abono . "</p>
                <br>
                <p style='margin-left: 100px; margin-top: 3px; width: 160px; display: block; float: left;'>" . $saldo . "</p>
                <p style='margin-left: 0px; margin-top: 6px; width: 115px; display: block; float: left;'>Cuota: " . $cuota . "</p>
                <br>
                <br> 
                <p style='margin-left: 110px; display: block; margin-top: 20px; width: 500px;' class='letraPeque'>" . $barrio . "<label style='margin-left: 195px; margin-top:-10px; display: block; max-height: 30px; width: 230px;' class='letraPeque'>" . $observaciones . "</label></p>
                <p style='margin-left: 30px; margin-top: 20px; width: 315px; display: block; float: left;'>" . $cliente["Telefono1"] . ' - ' . $cliente["Telefono2"] . "</p>                              
            </div>
            ";

        return $html;
    }

    public function TablaRecibo1($data, $cliente, $dataAdmin) {
        if ($data["Estado"] == 116) {
            $tel3 = "";
            if ($dataAdmin[0]["Telefono3"] != "") {
                $tel3 = " - " . $dataAdmin[0]["Telefono3"];
            }

            $html = '<style>@font-face{font-family:"Yesteryear";src: url("' . base_url('Public') . '/assets/fonts/Yesteryear-Regular.ttf");}
            *{color:#2196f3;}p{margin:0px;padding:0px;color:black;}</style>
        <table style="border:1px solid;border-radius:5px;margin-left:30px;margin-bottom:20px;">
            <tr>
                <td>
                    <table style="border:none;margin:0px auto;">
                        <tr>
                            <td style="border:none;" rowspan="2">
                                <img src="' . base_url() . '/Public/images/logo/01.png" width="70">
                            </td>
                            <td style="border:none;vertical-align:middle;text-align:center;">
                                <img src="' . base_url() . '/Public/images/logo/02.png" width="70" style="">
                                <img src="' . base_url() . '/Public/images/logo/03.png" width="70" style="">
                            </td>
                            <td rowspan="2">&nbsp;&nbsp;</td>
                            <td style="border:none;vertical-align:top;">
                                <p style="text-align:center;color:#2196f3;font-family:Yesteryear;font-size:30px;">
                                    ' . $dataAdmin[0]["Nombre"] . '
                                </p>
                            </td>
                            <td style="border:none;" rowspan="3">
                                <img src="' . base_url() . '/Public/images/logo/07.png" width="85" >                    
                            </td>
                        </tr>
                        <tr>
                            <td style="border:none;">
                                <p style="text-align:center;color:#2196f3;font-family:Yesteryear;font-size:30px;">
                                ' . $this->config->item('NAME_CEO') . '                                  
                                </p>
                            </td>
                            <td rowspan="2" style="border:none;vertical-align:top;">
                                <p style="text-align:center;color:#2196f3;">
                                    ' . strtoupper($dataAdmin[0]["Cargo"]) . '
                                </p>
                                <p style="text-align:center;color:#2196f3;">
                                    Teléfonos: ' . $dataAdmin[0]["Telefono1"] . '
                                </p>
                                <p style="text-align:center;color:#2196f3;">
                                    ' . $dataAdmin[0]["Telefono2"] . $tel3 . '
                                </p>
                            </td>
                        </tr>                        
                        <tr>
                            <td style="border:none;" colspan="3">
                                <p style="text-align:left;color:#2196f3;">
                                    ' . $this->config->item('NIT_COMPANY') . '  ' . $this->config->item('REG_COMPANY') . '
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <table style="border:2px solid #2196f3;border-radius:10px;width:600px;">
                        <tr style="border:none;">
                            <td style="width:70px;height:20px;">
                                Fecha:
                            </td>
                            <td style="border-bottom:1px solid;max-height:20px;">
                                <p>&nbsp;&nbsp;' . date("d/m/Y", strtotime($data["FechaProgramada"])) . ' al ' . date("d/m/Y", strtotime($data["FechaProgramada"] . "+ 8 days")) . '</p> 
                            </td>
                        </tr>
                        <tr style="border:none;">
                            <td style="width:70px;">
                                Nombre: 
                            </td>
                            <td style="border-bottom:1px solid;">
                                <p>&nbsp;&nbsp;' . $cliente["Nombre"] . '</p>
                            </td>
                        </tr>
                        <tr style="border:none;">
                            <td style="width:70px;">
                                Dirección: 
                            </td>
                            <td style="border-bottom:1px solid;">
                                <p>&nbsp;&nbsp;' . $cliente["Dir"] . '</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <table style="border:2px solid #2196f3;border-radius:10px;width:600px;">
                        <tr>
                            <td style="width:16.6%;">
                                ETAPA
                            </td>
                            <td style="width:16.6%;">
                                TORRE
                            </td>
                            <td style="width:16.6%;">
                                APTO
                            </td>
                            <td style="width:16.6%;">
                                MANZANA
                            </td>
                            <td style="width:16.6%;">
                                INT
                            </td>
                            <td style="width:16.6%;">
                                CASA
                            </td>
                        </tr>
                        <tr>
                            <td style="border:1px solid;padding:2px;text-align:center;">
                                <p style="margin:0px auto;">' . $cliente["Etapa"] . '</p>
                            </td>
                            <td style="border:1px solid;padding:2px;text-align:center;">
                                <p style="margin:0px auto;">' . $cliente["Torre"] . '</p>
                            </td>
                            <td style="border:1px solid;padding:2px;text-align:center;">
                                <p style="margin:0px auto;">' . $cliente["Apartamento"] . '</p>
                            </td>
                            <td style="border:1px solid;padding:2px;text-align:center;">
                                <p style="margin:0px auto;">' . $cliente["Manzana"] . '</p>
                            </td>
                            <td style="border:1px solid;padding:2px;text-align:center;">
                                <p style="margin:0px auto;">' . $cliente["Interior"] . '</p>
                            </td>
                            <td style="border:1px solid;padding:2px;text-align:center;">
                                <p style="margin:0px auto;">' . $cliente["Casa"] . '</p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="1" style="text-align:center;">
                                VALOR: 
                            </td> 
                            <td colspan="2" style="border-bottom:1px solid;">
                                <p>&nbsp;&nbsp;&nbsp;' . money_format("%.0n", $data["Valor"]) . '</p>
                            </td>
                            <td colspan="1" style="text-align:center;">
                                ABONO: 
                            </td> 
                            <td colspan="2" style="border-bottom:1px solid;">
                                <p>&nbsp;&nbsp;&nbsp;' . money_format("%.0n", $data["Cuota"]) . '</p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="1" style="text-align:center;">
                                SALDO: 
                            </td> 
                            <td colspan="2" style="border-bottom:1px solid;">
                                <p>&nbsp;&nbsp;&nbsp;' . money_format("%.0n", $data["Valor"] - $data["Cuota"]) . '</p>
                            </td>
                            <td colspan="3" style="text-align:center;">
                                <p><b>1 BIBLIA GRANDE</b></p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <table style="border:2px solid #2196f3;border-radius:10px;width:600px;">
                        <tr style="border:none;">
                            <td style="width:10%;">
                                BARRIO:
                            </td>
                            <td style="width:40%;">
                                <p>' . $cliente["Barrio"] . '</p> 
                            </td>
                            <td style="width:10%;">
                                CUOTA: 
                            </td>
                            <td style="width:5%;">
                                <p>' . $data["numCuotas"] . '</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <table style="border:2px solid #2196f3;border-radius:10px;width:600px;">
                        <tr style="border:none;">
                            <td style="width:20%">TEL. CLIENTE:</td>
                            <td style="width:35%">
                                <p>' . $cliente["Telefono1"] . ' - ' . $cliente["Telefono2"] . '</p> 
                                <p>' . $cliente["Telefono3"] . '</p> 
                            </td>
                            <td style="width:20%">
                                FIRMA: 
                            </td>
                            <td style="width:25%"><br></td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <p style="text-align:center;font-size:20px;"><b>E-mail: ' . $this->config->item('EMAIL1_COMPANY') . ' - ' . $this->config->item('EMAIL2_COMPANY') . '</b></p>
                </td>            
            </tr>
            <tr>
                <td colspan="4">
                    <p style="text-align:center;font-size:20px;"><b>' . $dataAdmin[0]["Direccion"] . ' - Teléfono: ' . $dataAdmin[0]["Telefono1"] . '</b></p>
                </td>          
            </tr>
        </table>';

            return $html . "<br>";
        }
    } 

    public function ConteoPagosPost() {
        $fecha1 = trim($this->input->post('pag_fec1') . " 00:00:00");
        $fecha1 = preg_replace('#(\d{2})/(\d{2})/(\d{4})\s(.*)#', '$3-$2-$1 $4', $fecha1);
        $fecha2 = trim($this->input->post('pag_fec2') . " 23:59:59");
        $fecha2 = preg_replace('#(\d{2})/(\d{2})/(\d{4})\s(.*)#', '$3-$2-$1 $4', $fecha2);
        $Pagos = $this->ConteoPagos($fecha1, $fecha2);

        echo json_encode($Pagos);
    }

    public function ConteoPagos($fecha1, $fecha2) {
        $Pagos = array();
        $progH = $this->Pagos_model->AllPayProg();
        $Pagos["ProgH"] = $progH[0]["Num"];
        $todos = $this->Pagos_model->AllPay();
        $Pagos["Todos"] = $todos[0]["Num"];
        $DescH = $this->Pagos_model->AllPayProgDesc();
        $Pagos["DescH"] = $DescH[0]["Num"];
        $NoPagoH = $this->Pagos_model->AllPayProgNoPago();
        $Pagos["NoPagoH"] = $NoPagoH[0]["Num"];
        //Filtro
        $Confir = $this->Pagos_model->PayOk($fecha1, $fecha2);
        $Pagos["Confir"] = $Confir[0]["Num"];
        $PagPro = $this->Pagos_model->PayProg($fecha1, $fecha2);
        $Pagos["PagPro"] = $PagPro[0]["Num"];
        $Desc = $this->Pagos_model->PayProgDesc($fecha1, $fecha2);
        $Pagos["Desc"] = $Desc[0]["Num"];
        $NoPago = $this->Pagos_model->PayProgNoPago($fecha1, $fecha2);
        $Pagos["NoPago"] = $NoPago[0]["Num"];
        //Llamadas Pedidos
        $Llamadas = $this->Pedidos_model->contarPedidos($fecha1, $fecha2);
        $Pagos["Llamadas"] = $Llamadas[0]["Num"];

        return $Pagos;
    }

    public function AdminPagos() {
        $idPermiso = 30;
        $page = validarPermisoPagina($idPermiso);

        $dataUsuarios = $this->Usuarios_model->obtenerUsuariosEP();
        $fecha = date("Y-m-d");
        $user = "*";

        $data = new stdClass();
        $data->Controller = "Pagos";
        $data->title = "Pagos Confirmados";
        $data->subtitle = "Pagos Confirmados";
        $data->contenido = $this->viewControl . '/AdminPagos';
        $data->ListaUsuarios = $dataUsuarios;

        $this->load->view('frontend', $data);
    }

    public function pagosListado() {
        $user = "*";
        $fechaIni = date('Y-m-d') . " 00:00:00";
        $fechaFin = date('Y-m-d') . " 23:59:59";

        try {
            $dataProgramados = $this->Pagos_model->obtenerPagosFechaUser($user, $fechaIni, $fechaFin);
            $arreglo["data"] = [];
            $idPermiso = 30;
            $page = validarPermisoBoton($idPermiso);

            if ($page) {
                if (isset($dataProgramados) && $dataProgramados != FALSE) {
                    $i = 0;
                    foreach ($dataProgramados as $item) {   
                        $btn1 = "";
                        $btn2 = "";
                        $btn3 = "";

                        $idPermiso = 19;
                        $btn = validarPermisoBoton($idPermiso);

                        if ($btn) {
                            $btn1 = "<a href='" . base_url() . "Pagos/Consultar/" . $item['Codigo'] . "/' target='_blank' title='Ver Pago'><i class='fa fa-search' aria-hidden='true' style='padding:5px;'></i></a>";                
                        }

                        $idPermiso = 31;
                        $btn = validarPermisoBoton($idPermiso);

                        if ($btn) {
                            $btn2 = "<a href='" . base_url() . "Pagos/Reversar/" . $item['Codigo'] . "/' target='_blank' title='Reversar Pago'><i class='fa fa-undo' aria-hidden='true' style='padding:5px;'></i></a>";
                        }

                        if (strlen($item["Observaciones"]) > 30) {
                            $osb = substr($item["Observaciones"], 0, 30) . " (...)";
                        } else {
                            $osb = $item["Observaciones"];
                        }

                        $pedidosClientes = $this->Pedidos_model->obtenerClientePorPedido($item["Pedido"]);
                        $arreglo["data"][$i] = array(
                            "pedido" => $pedidosClientes[0]["Nombre"],
                            "cuota" => money_format("%.0n", $item["Pago"]),
                            "fecha" => date("d/m/Y", strtotime($item["FechaPago"])),
                            "observacion" => $osb,
                            "usuario" => $item["UsuarioCreacion"],
                            "btn" => '<div class="btn-group text-center" style="margin: 0px auto; width:100%;">' . $btn1 . $btn2 . $btn3 . '</div>'
                        );
                        $i++;
                    }
                } 
            }
            echo json_encode($arreglo);
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage() . "<br>";
        }
    }

    public function FiltroPagos() { 
        $user = trim($this->input->post('pag_usu'));
        $fechaIni = trim($this->input->post('pag_fec1'));
        $date = str_replace('/', '-', $fechaIni);
        $fechaIni = date('Y-m-d', strtotime($date)) . " 00:00:00";
        $fechaFin = trim($this->input->post('pag_fec2'));
        $date = str_replace('/', '-', $fechaFin);
        $fechaFin = date("Y-m-d", strtotime($date)) . " 23:59:59";

        try {
            $dataProgramados = $this->Pagos_model->obtenerPagosFechaUser($user, $fechaIni, $fechaFin);
            $arreglo["data"] = [];
            $idPermiso = 30;
            $page = validarPermisoBoton($idPermiso);

            if ($page) {
                if (isset($dataProgramados) && $dataProgramados != FALSE) {
                    $i = 0;
                    foreach ($dataProgramados as $item) {
                        $btn1 = "";
                        $btn2 = "";
                        $btn3 = "";

                        $idPermiso = 19;
                        $btn = validarPermisoBoton($idPermiso);

                        if ($btn) {
                            $btn1 = "<a href='" . base_url() . "Pagos/Consultar/" . $item['Codigo'] . "/' title='Ver Pago'><i class='fa fa-search' aria-hidden='true' style='padding:5px;'></i></a>";
                        }

                        $idPermiso = 31;
                        $btn = validarPermisoBoton($idPermiso);

                        if ($btn) {
                            $btn2 = "<a href='" . base_url() . "Pagos/Reversar/" . $item['Codigo'] . "/' title='Reversar Pago'><i class='fa fa-undo' aria-hidden='true' style='padding:5px;'></i></a>";
                        }

                        if (strlen($item["Observaciones"]) > 30) {
                            $osb = substr($item["Observaciones"], 0, 30) . " (...)";
                        } else {
                            $osb = $item["Observaciones"];
                        }

                        $pedidosClientes = $this->Pedidos_model->obtenerClientePorPedido($item["Pedido"]);
                        $arreglo["data"][$i] = array(
                            "pedido" => $pedidosClientes[0]["Nombre"],
                            "cuota" => money_format("%.0n", $item["Pago"]),
                            "fecha" => date("d/m/Y", strtotime($item["FechaPago"])),
                            "observacion" => $osb,
                            "usuario" => $item["UsuarioCreacion"],
                            "btn" => '<div class="btn-group text-center" style="margin: 0px auto; width:100%;">' . $btn1 . $btn2 . $btn3 . '</div>'
                        );
                        $i++;
                    }
                }
            }
            echo json_encode($arreglo);
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage() . "<br>";
        } 
    }

    public function Reversar($pago) {
        $dataPago = $this->Pagos_model->obtenerPagosCod($pago);
        if (isset($dataPago) && $dataPago != FALSE) {
            $dataPedido = $this->Pedidos_model->obtenerProductosPedidoClienteAll($dataPago[0]["Pedido"]);
            if (isset($dataPedido) && $dataPedido != FALSE) {
                $dataClientes = $this->Clientes_model->obtenerClienteDir($dataPedido[0]["Cliente"]);
                if (isset($dataClientes) && $dataClientes != FALSE) {
                    $dataCobradores = $this->Cobradores_model->obtenerCobradores();
                    if (isset($dataCobradores) && $dataCobradores != FALSE) {

                        $dataPagoPedido = array();
                        foreach ($dataPedido as $item) {
                            $i = intval($item['CodPedido']);
                            $dataPagos = $this->Pagos_model->obtenerPagosPedido($i);
                            if (isset($dataPagos) && $dataPagos != FALSE) {
                                $dataPagoPedido[$i] = $dataPagos;
                            }
                        }

                        $datosPagos = array();
                        $dataPagosP = array();
                        foreach ($dataPedido as $item1) {
                            $i = intval($item1['CodPedido']);
                            if (array_key_exists($i, $dataPagoPedido)) {
                                $dataPagosP["Pedidos"] = $i;
                                $cuota = 0;
                                $abonado = 0;
                                $f2 = "2018-01-01 00:00:00";
                                foreach ($dataPagoPedido[$i] as $item) {
                                    $cuota ++;
                                    $abonado = $abonado + $item["Pago"];
                                    $f1 = $item["FechaPago"];
                                    if ($f1 > $f2) {
                                        $f2 = $f1;
                                    }
                                }
                                $dataPagosP["cuota"] = $cuota;
                                $dataPagosP["abonado"] = $abonado;
                                $dataPagosP["valor"] = $item1["Valor"];
                                $dataPagosP["saldo"] = intval($item1["Valor"]) - intval($abonado);
                                $dataPagosP["fechaUltimoPago"] = $f2;
                                $ultimoPago = 0;

                                foreach ($dataPagoPedido[$i] as $item) {
                                    if ($item["FechaPago"] == $f2) {
                                        if ($item["Pago"] > $ultimoPago) {
                                            $ultimoPago = $item["Pago"];
                                        }
                                    }
                                }
                                $dataPagosP["UltimoPago"] = $ultimoPago;
                                $datosPagos[$i] = $dataPagosP;
                            } else {
                                $dataPagosP["Pedidos"] = $i;
                                $dataPagosP["cuota"] = "0";
                                $dataPagosP["abonado"] = "0";
                                $dataPagosP["valor"] = $item1["Valor"];
                                $dataPagosP["saldo"] = intval($item1["Saldo"]);
                                $dataPagosP["fechaUltimoPago"] = "--";
                                $ultimoPago = 0;
                                $dataPagosP["UltimoPago"] = 0;
                                $datosPagos[$i] = $dataPagosP;
                            }
                        }

                        $data = new stdClass();
                        $data->Controller = "Pagos";
                        $data->title = "Reversar Pago";
                        $data->subtitle = "Reversar Pago";
                        $data->contenido = $this->viewControl . '/Reversar';
                        $data->cliente = $dataPedido[0]["Cliente"];
                        $data->pedido = $dataPedido[0]["CodPedido"];
                        $data->valor = $dataPedido[0]["Valor"];
                        $data->codigo = $pago;
                        $data->ListaDatos = $dataPedido;
                        $data->ListaDatos2 = $dataClientes;
                        $data->ListaDatos3 = $datosPagos;
                        $data->dataPago = $dataPago;
                        $data->Lista1 = $dataCobradores;

                        $this->load->view('frontend', $data);
                    } else {
                        $this->session->set_flashdata("error", "No se encontraron datos de los Cobradores.");
                        redirect(base_url("/Cobradores/Admin/"));
                    }
                } else {
                    $this->session->set_flashdata("error", "No se encontraron datos del Cliente: <b>$cliente</b>");
                    redirect(base_url("/Clientes/Admin/"));
                }
            } else {
                $this->session->set_flashdata("error", "No se encontraron datos del Pedido del Pago: <b>$cliente</b>");
                redirect(base_url("/Clientes/Admin/"));
            }
        } else {
            $this->session->set_flashdata("error", "No se encontraron datos de la Programación del Pago");
            redirect(base_url("/Pagos/Admin/"));
        }
    }

    public function Reverse() {
        //Datos Pago
        $pag_cod = trim($this->input->post('pag_cod'));
        $pag_cli = trim($this->input->post('pag_cli'));
        $pag_ped = trim($this->input->post('pag_ped'));
        $pag_cuo = trim($this->input->post('pag_cuo'));
        $pag_pag = trim($this->input->post('pag_pag'));
        $pag_obs = trim($this->input->post('pag_obs'));
        $pag_obsAnt = trim($this->input->post('pag_obsAnt'));
        //Datos Auditoría
        $user = $this->session->userdata('Usuario');
        $fecha = date("Y-m-d H:i:s");

        try {
            $Pag = $this->Pagos_model->obtenerPagosCod($pag_cod);
            if ($Pag) {
                $dataPago = array(
                    "Habilitado" => 0,
                    "Observaciones" => $pag_obsAnt . "\n---\nSe Reversa Pago:\n" . $pag_obs,
                    "UsuarioModificacion" => $user,
                    "FechaModificacion" => $fecha
                );
                if ($this->Pagos_model->update($pag_cod, $dataPago)) {
                    $dataPedido = $this->Pedidos_model->obtenerPedido($pag_ped);
                    $DiaCobro = $dataPedido[0]["DiaCobro"];
                    $DiaCobro = date("Y-m-d H:i:s", strtotime($DiaCobro . "- 1 month"));
                    $saldo = intval($dataPedido[0]["Saldo"]) - intval($pag_pag);
                    $pag_cuo = $pag_cuo - 1;
                    //Se Crea Historial Pago
                    $this->History($pag_cli, $pag_ped, $fecha, $user, "Reversar Pago", $dataPedido[0]["Saldo"], $pag_cuo, $saldo, $pag_pag, $pag_obs);
                    $dataActPedido = array(
                        "DiaCobro" => $DiaCobro,
                        "Saldo" => $saldo,
                        "Estado" => 110,
                        "FechaUltimoPago" => date("Y-m-d H:i:s", strtotime("2018-06-01 00:00:00")),
                        "Observaciones" => "Actualización de Saldo del Pedido:\nSaldo Anterior: " . money_format("%.0n", ($dataPedido[0]["Saldo"]))
                        . "\nSaldo Actual: " . money_format("%.0n", ($saldo)) . "\n\nObservación automática.",
                        "UsuarioModificacion" => $user,
                        "FechaModificacion" => $fecha
                    );

                    if ($this->Pedidos_model->update($pag_ped, $dataActPedido)) {
                        $modulo = "Pagar Pedido";
                        $tabla = "Pagos";
                        $accion = "Actualizar Saldo";
                        $data = compararCambiosLog($dataPedido, $dataActPedido);
                        //var_dump($data);
                        if (count($data) > 2) {
                            $data['Codigo'] = $pag_ped;
                            $llave = $pag_ped;
                            $sql = LogSave($data, $modulo, $tabla, $accion, $llave);
                        }

                        $dataCli = array(
                            "Estado" => 104,
                            "Observaciones" => $pag_obsAnt . "\n---\nCambio de Estado por Reversion de Pago\n \nObservación automática.",
                            "UsuarioModificacion" => $user,
                            "FechaModificacion" => $fecha
                        );
                        if ($this->Clientes_model->update($dataPedido[0]["Cliente"], $dataCli)) {
                            $dataCliente = $this->Clientes_model->obtenerCliente($dataPedido[0]["Cliente"]);
                            $modulo = "Pagar Pedido";
                            $tabla = "Clientes";
                            $accion = "Estado Al día Cliente";
                            $data = compararCambiosLog($dataCliente, $dataCli);
                            //var_dump($data);
                            if (count($data) > 2) {
                                $data['Codigo'] = $pag_ped;
                                $data['Observaciones'] = "nEstado: Al día\n---\nSe actualiza estado del Cliente\n \nObservación automática.";
                                $llave = $pag_ped;
                                $sql = LogSave($data, $modulo, $tabla, $accion, $llave);
                            }
                            echo 1;
                        } else {
                            echo "No se pudo Actualizar el Estado del Cliente. Actualice la página y vuelva a intentarlo.";
                        }
                    } else {
                        echo "No se pudo Actualizar el Saldo del Pedido. Actualice la página y vuelva a intentarlo.";
                    }
                } else {
                    echo "No se pudo actualizar el Pago. Actualice la página y vuelva a intentarlo.";
                }
            } else {
                echo "No se pudo encontrar el Pago. Actualice la página y vuelva a intentarlo.";
            }
        } catch (Exception $e) {
            echo 'Ha habido una excepción: ' . $e->getMessage() . "<br>";
        }
    }

    public function valPagosGestion($dataPagos) {
        $fecha1 = date("Y-m-d", strtotime(date("Y-m-d") . "+ 8 days")) . " 23:59:59";
        $fecha2 = date("Y-m-d", strtotime(date("Y-m-d") . "- 8 days")) . " 00:00:00";
        $i = 0;
        foreach ($dataPagos as $value) {
            $pedido = $value["Pedidos"];
            $cliente = $value["codCliente"];
            $gest = $this->Cobradores_model->obtenerLlamadasPedidoFechas($pedido, $cliente, $fecha2, $fecha1, "ASC");
            $Motivo = "Pendiente";
            $color = "";
            $codMotivo = "100";
            $contHabilitados = 0; 
            $habilitado = 0; 

            if ($gest != FALSE) {
                $Motivo = "Pendiente";
                $color = "";
                $codMotivo = "100";
                 
                foreach ($gest as $val) {                    
                    $habilitado = $val['Habilitado'];
                    if ($habilitado == 1) {
                        $Motivo = $val["nombreMotivo"];
                        $color = $val["color"];
                        $codMotivo = $val["codMotivo"];
                    } 
                } 
                $dataPagos[$pedido]['Motivo'] = $Motivo;
                $dataPagos[$pedido]['Color'] = $color; 
            } else {
                $ultimoPagoProgramado = $this->Pagos_model->ultimoPagosProgramadosPorPedido($pedido, $fecha2, $fecha1);
                if ($ultimoPagoProgramado!=FALSE){
                    $Motivo = "Pago Programado";
                    $color = "green";
                    $codMotivo = "101";
                }
                
                $dataPagos[$pedido]['Motivo'] = $Motivo;
                $dataPagos[$pedido]['Color'] = $color;
            }
            $dataPagos[$pedido]['CodMotivo'] = $codMotivo;             
        }
  
    return $dataPagos;
    }

    public function getNextDayPay($DiaCobro)
    {
        // Calcular Próxima Fecha de pago
        $fecha = date("Y-m-d H:i:s");
        $dia = date("d", strtotime($DiaCobro . "+ 1 month"));
        $mes = date("m", strtotime($fecha . "+ 1 month"));
        // Inicio Cambio
        $anio = date("Y", strtotime($fecha . "+ 1 month"));
        //$anio = date("Y", strtotime($DiaCobro));
        //if ($mes == "01"){
        //    $anio = date("Y", strtotime($DiaCobro . "+ 1 year"));
        //}
        // Fin Cambio
        $proximoPago = date("Y-m-d H:i:s", strtotime($anio . "-" . $mes . "-" . $dia . " 00:00:00"));
        $proximoPago = date("Y-m-d H:i:s", strtotime($proximoPago));

        return $proximoPago;
    }
    
    
    
    public function pdfNelson() {
        // Introducimos HTML de prueba
        //$url = $this->view('Pdf');
        
        //$html = $this->contents_curl($url);
        $html = "
        <style>
            * {
                color:#000;
            }
            p {
                margin:0px;
                padding:0px;
            }
            .letraPeque{
                min-height: 40px; 
                max-height: 40px; 
                font-size: 13px; 
                line-height: 80%;
            }
        </style>
        <div style='width: 490px; margin-top: 0px; border: 0px solid; margin-top: -7px;'>
            <p style='margin: 20px 0 -10px; text-align: right; font-weight: bold;'>465</p>
            <br />
            <br />
            <p style='margin-left: 400px;color: red;'>018061</p>
            <br />
            <br />
            <p style='margin-left: 110px; margin-top: -15px; '>04 de Marzo del 2021 al 12 de Marzo del 2021</p>
            <p style='margin-left: 125px; margin-top: 5px; '>Luz Florez</p>
            <p style='margin-left: 125px; margin-top: 0px; width: 215px; display: block; float: left;' class='letraPeque'>Call 39 D 68 J 53 Sur 2 Piso Bar: New York</p>
            <p style='margin-left: 30px; margin-top: 0px; width: 115px; display: block; float: left;' class='letraPeque'>30012345678 - 3101234567</p>
            <br />
            <br />
            <br />
            <div style='display: block; margin: 10px 0px 0px;'>
                <label style='margin-left: 30px;'></label>
                <label style='margin-left: 60px;'></label>
                <label style='margin-left: 60px;'></label>
                <label style='margin-left: 75px;'></label>
                <label style='margin-left: 75px;'></label>
                <label style='margin-left: 60px;'></label>
            </div>
            <p style='margin-left: 100px; display: block; float: left;'>$ 200.000</p>
            <br>
            <p style='margin-left: 100px; display: block; float: left;'>$ 20.000</p>
            <p style='margin-left: 220px; display: block; float: left;'>3</p>
            <br>
            <p style='margin-left: 100px; display: block; float: left;'>$ 140.000</p>
            <br>
            <br>
            <p style='margin-left: 30px; display: block; margin-top: 0px; width: auto; line-height: 100%;' class='letraPeque'>Cliente indica que quiere pagar $ 20.000 el día 04/03/2021.pasar ch deja la cuota hijo</p>
        </div>";
        
        
        for ($i = 1; $i <= 100; $i++) {
            echo $html;
        }
        // // Instanciamos un objeto de la clase DOMPDF.
        // $pdf = new DOMPDF();
         
        // // Definimos el tamaño y orientación del papel que queremos.
        // $pdf->set_paper("A6", "landscape");
        // //$pdf->set_paper(array(0,0,104,250));
         
        // // Cargamos el contenido HTML.
        // $pdf->load_html(utf8_decode($html));
         
        // // Renderizamos el documento PDF.
        // $pdf->render();
         
        // // Enviamos el fichero PDF al navegador.
        // $pdf->stream('reportePdf.pdf');
    }
    
    public function contents_curl($url) {
        try {
        	$crl = curl_init();
        	$timeout = 5;
        	curl_setopt($crl, CURLOPT_URL, $url);
        	curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
        	curl_setopt($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
        	$ret = curl_exec($crl);
        	curl_close($crl);  
        } catch(Exception $e) {  
            var_dump($e);
        }
        
        die();
        return $ret;
    }
 
	
}

?>