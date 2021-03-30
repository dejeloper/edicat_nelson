<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Reportes_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        
    }

    public function pagosUsuarioFechas($usuario, $fechaI, $fechaF) {
        $this->db->select('p.*, cli.Nombre as NomCliente');
        $this->db->from('Pagos as p');
        $this->db->join('Clientes as cli', 'cli.Codigo = p.Cliente');
        if ($usuario != "*") {
            $this->db->where('p.UsuarioCreacion', $usuario);
        }
        $this->db->where('p.FechaPago >=', $fechaI);
        $this->db->where('p.FechaPago <=', $fechaF);
        $query = $this->db->get();
        if ($query->num_rows() <= 0) {
            return false;
        } else {
            return $query->result_array();
        }
    }

    public function AbonosUsuarioFechas($usuario, $fechaI, $fechaF) {
        $this->db->select('p.*, cli.Nombre as NomCliente');
        $this->db->from('Pagos as p');
        $this->db->join('Clientes as cli', 'cli.Codigo = p.Cliente');
        if ($usuario != "*") {
            $this->db->where('p.UsuarioCreacion', $usuario);
        }
        $this->db->where('p.Confirmacion', null);
        $this->db->where('p.FechaPago >=', $fechaI);
        $this->db->where('p.FechaPago <=', $fechaF);
        $query = $this->db->get();
        if ($query->num_rows() <= 0) {
            return false;
        } else {
            return $query->result_array();
        }
    }

    public function pagosProgramadosUsuarioFechas($usuario, $fechaI, $fechaF) {
        $this->db->select('p.*, cli.Nombre as NomCliente, est.Nombre as NomEstado');
        $this->db->from('PagosProgramados as p');
        $this->db->join('Estados as est', 'est.Codigo = p.Estado');
        $this->db->join('Pedidos as ped', 'ped.Codigo = p.Pedido');
        $this->db->join('Clientes as cli', 'cli.Codigo = ped.Cliente');
        if ($usuario != "*") {
            $this->db->where('p.UsuarioCreacion', $usuario);
        }
        $this->db->where('p.FechaProgramada >=', $fechaI);
        $this->db->where('p.FechaProgramada <=', $fechaF);
        $this->db->order_by("p.FechaProgramada, p.Estado");
        $query = $this->db->get();
        if ($query->num_rows() <= 0) {
            return false;
        } else {
            return $query->result_array();
        }
    }

    public function pagosyProgramadosUsuarioFechas($usuario, $fechaI, $fechaF) {
        $this->db->select('p.*, pag.*, cli.Nombre as NomCliente, est.Nombre as NomEstado');
        $this->db->from('Pagos as pag');
        $this->db->join('PagosProgramados as p', 'p.Codigo = pag.Confirmacion');
        $this->db->join('Estados as est', 'est.Codigo = p.Estado');
        $this->db->join('Pedidos as ped', 'ped.Codigo = p.Pedido');
        $this->db->join('Clientes as cli', 'cli.Codigo = ped.Cliente');
        if ($usuario != "*") {
            $this->db->where('p.UsuarioCreacion', $usuario);
        }
        $this->db->where('p.FechaProgramada >=', $fechaI);
        $this->db->where('p.FechaProgramada <=', $fechaF);
        $this->db->order_by("p.FechaProgramada, p.Estado");
        $query = $this->db->get();
        if ($query->num_rows() <= 0) {
            return false;
        } else {
            return $query->result_array();
        }
    }

    public function pagosDescartadosUsuarioFechas($usuario, $fechaI, $fechaF) {
        $this->db->select('p.*, cli.Nombre as NomCliente, est.Nombre as NomEstado');
        $this->db->from('PagosProgramados as p');
        $this->db->join('Estados as est', 'est.Codigo = p.Estado');
        $this->db->join('Pedidos as ped', 'ped.Codigo = p.Pedido');
        $this->db->join('Clientes as cli', 'cli.Codigo = ped.Cliente');
        if ($usuario != "*") {
            $this->db->where('p.UsuarioCreacion', $usuario);
        }
        $this->db->where('p.Estado', 122); //Descartado
        $this->db->where('p.FechaProgramada >=', $fechaI);
        $this->db->where('p.FechaProgramada <=', $fechaF);
        $this->db->order_by("p.FechaProgramada, p.Estado");
        $query = $this->db->get();
        if ($query->num_rows() <= 0) {
            return false;
        } else {
            return $query->result_array();
        }
    }

    public function obtenerPago($pagoProgramado) {
        $this->db->select('Codigo');
        $this->db->where('Confirmacion', $pagoProgramado);
        $query = $this->db->get('Pagos');
        if ($query->num_rows() <= 0) {
            return 0;
        } else {
            return $query->result_array();
        }
    }

    public function obtenerTotalValoresPorEstado($estados){
        $this->db->select('SUM(p.Saldo) as Total, COUNT(*) as Num_Clientes, e.Nombre');
        $this->db->from('Pedidos as p');
        $this->db->join('Clientes as c', 'c.Codigo = p.Cliente'); 
        $this->db->join('Estados as e', 'e.Codigo = c.Estado'); 
        $this->db->where_in('c.Estado', $estados);
        $this->db->group_by("c.Estado");      
        $query = $this->db->get();
        // echo $this->db->last_query(); die();
        if ($query->num_rows() <= 0) {
            return 0;
        } else {
            return $query->result_array();
        }
    }

    public function obtenerTotalValores($estados){ 
        $this->db->select('SUM(p.Saldo) as Total');
        $this->db->from('Pedidos as p');
        $this->db->join('Clientes as c', 'c.Codigo = p.Cliente'); 
        $this->db->join('Estados as e', 'e.Codigo = c.Estado'); 
        $this->db->where_in('c.Estado', $estados);
        $query = $this->db->get();
        // echo $this->db->last_query(); die();
        if ($query->num_rows() <= 0) {
            return 0;
        } else {
            return $query->result_array();
        }
    }


    //Conteo
    //Todos Los Pagos
    public function AllPay() {
        $this->db->select('Count(*) as Num');
        $query = $this->db->get('Pagos');
        if ($query->num_rows() <= 0) {
            return 0;
        } else {
            return $query->result_array();
        }
    }

    //Todos los Pagos Programados
    public function AllPayProg() {
        $this->db->select('Count(*) as Num');
        $query = $this->db->get('PagosProgramados');
        if ($query->num_rows() <= 0) {
            return 0;
        } else {
            return $query->result_array();
        }
    }

    //Todos los pagos Descartados
    public function AllPayProgDesc() {
        $this->db->select('Count(*) as Num');
        $this->db->where('Estado', 122); //Descartado
        $query = $this->db->get('PagosProgramados');
        if ($query->num_rows() <= 0) {
            return 0;
        } else {
            return $query->result_array();
        }
    }

    //Todos los pagos Descartados
    public function AllPayProgNoPago() {
        $this->db->select('Count(*) as Num');
        $this->db->where("(Estado = '116' OR Estado = '118')"); //Programado o No Pagado
        $query = $this->db->get('PagosProgramados');
        if ($query->num_rows() <= 0) {
            return 0;
        } else {
            return $query->result_array();
        }
    }

    //Pagos Realizados
    public function PayOk($fechaI, $fechaF) {
        $this->db->select('Count(*) as Num');
        $this->db->where('FechaPago >=', $fechaI);
        $this->db->where('FechaPago <=', $fechaF);
        $query = $this->db->get('Pagos');
        if ($query->num_rows() <= 0) {
            return 0;
        } else {
            return $query->result_array();
        }
    }

    //Pagos Programados
    public function PayProg($fechaI, $fechaF) {
        $this->db->select('Count(*) as Num');
        $this->db->where('FechaProgramada >=', $fechaI);
        $this->db->where('FechaProgramada <=', $fechaF);
        $query = $this->db->get('PagosProgramados');
        if ($query->num_rows() <= 0) {
            return 0;
        } else {
            return $query->result_array();
        }
    }

    //Pagos Descartados
    public function PayProgDesc($fechaI, $fechaF) {
        $this->db->select('Count(*) as Num');
        $this->db->where('Estado', 122); //Descartado
        $this->db->where('FechaProgramada >=', $fechaI);
        $this->db->where('FechaProgramada <=', $fechaF);
        $query = $this->db->get('PagosProgramados');
        if ($query->num_rows() <= 0) {
            return 0;
        } else {
            return $query->result_array();
        }
    }

    //Pagos Descartados
    public function PayProgNoPago($fechaI, $fechaF) {
        $this->db->select('Count(*) as Num');
        $this->db->where("(Estado = '116' OR Estado = '118')"); //Programado o No Pagado        
        $this->db->where('FechaProgramada >=', $fechaI);
        $this->db->where('FechaProgramada <=', $fechaF);
        $query = $this->db->get('PagosProgramados');
        if ($query->num_rows() <= 0) {
            return 0;
        } else {
            return $query->result_array();
        }
    }


}
