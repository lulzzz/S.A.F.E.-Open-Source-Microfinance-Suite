<?php
/**
 * @author Balam Gonzalez Luis Humberto
 * @version 0.0.01
 * @package
 */
//=====================================================================================================
	include_once("../core/go.login.inc.php");
	include_once("../core/core.error.inc.php");
	include_once("../core/core.html.inc.php");
	include_once("../core/core.init.inc.php");
	include_once("../core/core.db.inc.php");
	$theFile			= __FILE__;
	$permiso			= getSIPAKALPermissions($theFile);
	if($permiso === false){	header ("location:../404.php?i=999");	}
	$_SESSION["current_file"]	= addslashes( $theFile );
//=====================================================================================================
$xHP		= new cHPage("TR.SUCURSAL", HP_FORM);
$xQL		= new MQL();
$xLi		= new cSQLListas();
$xF			= new cFecha();
$xLoc		= new cLocal();
$xValid		= new cTiposLimpiadores();






//$jxc = new TinyAjax();
//$jxc ->exportFunction('datos_del_pago', array('idsolicitud', 'idparcialidad'), "#iddatos_pago");
//$jxc ->process();


$xHP->init();


$jscallback		= parametro("callback"); $tiny = parametro("tiny"); $form = parametro("form"); $action = parametro("action", SYS_NINGUNO);
$clave 			= parametro("codigo_sucursal", null, MQL_RAW); $clave = parametro("id", $clave, MQL_RAW);
$clave			= $xValid->cleanSucursal($clave);


$xTabla			= new cGeneral_sucursales();

if($clave !== ""){
	$xTabla->setData( $xTabla->query()->initByID($clave));
}
$xTabla->setData($_REQUEST);



$persona		= parametro("idsocio", $xTabla->clave_de_persona()->v(), MQL_INT);
$gerente		= parametro("gerente_sucursal", $xTabla->gerente_sucursal()->v(), MQL_INT);
$cumplimiento	= parametro("titular_de_cumplimiento", $xTabla->titular_de_seguimiento()->v(), MQL_INT);

//inicializar la persona
$xSoc			= new cSocio($persona);
$ODom			= null;
if($xSoc->init() == true){
	$ODom		= $xSoc->getODomicilio();
}
$xSel			= new cHSelect();
if($clave == null){
	$step		= MQL_ADD;
	$clave		= "";//$xTabla->query()->getLastID() + 1;
} else {
	$step		= MQL_MOD;
	if($clave != null){$xTabla->setData( $xTabla->query()->initByID($clave));}
}
$xFRM	= new cHForm("frmgeneral_sucursales", "sucursales.frm.php?action=$step");
$xFRM->setTitle($xHP->getTitle());


if($action == MQL_ADD){		//Agregar
	//$clave 		= parametro($xTabla->getKey(), null, MQL_RAW);
	
	
	
	if($clave !== ""){
		$xTabla->setData( $xTabla->query()->initByID($clave));
		$xTabla->setData($_REQUEST);
		$xTabla->codigo_sucursal( $clave );
		
		//modificar la parte de personas asociadas
		$xTabla->gerente_sucursal($gerente);
		$xTabla->titular_de_cumplimiento($cumplimiento);
		$xTabla->clave_de_persona($persona);
		
		if($ODom === null){
			$xTabla->calle( EACP_DOMICILIO_CALLE );
			$xTabla->codigo_postal( EACP_CODIGO_POSTAL );
			$xTabla->colonia( EACP_COLONIA );
			$xTabla->telefono( EACP_TELEFONO_PRINCIPAL );
			$xTabla->municipio( EACP_MUNICIPIO );
			$xTabla->localidad( EACP_CLAVE_DE_LOCALIDAD );
			$xTabla->estado( EACP_ESTADO );
			$xTabla->numero_exterior( EACP_DOMICILIO_NUM_EXT );
			$xTabla->numero_interior( EACP_DOMICILIO_NUM_INT );
		} else {
			$xTabla->calle( $ODom->getCalle() );
			$xTabla->codigo_postal( $ODom->getCodigoPostal() );
			$xTabla->colonia( $ODom->getColonia() );
			$xTabla->telefono( $xSoc->getTelefonoPrincipal() );
			$xTabla->municipio( $ODom->getMunicipio() );
			$xTabla->localidad( $ODom->getClaveDeLocalidad() );
			$xTabla->estado( $ODom->getEstado() );
			$xTabla->numero_exterior( $ODom->getNumeroExterior() );
			$xTabla->numero_interior( $ODom->getNumeroInterior() );
		}
		$xTabla->query()->insert()->save();
		$xFRM->addAvisoRegistroOK();
		$xFRM->addCerrar();
	}
	//Agregar nueva Caja Local
	
	
	if(SISTEMA_CAJASLOCALES_ACTIVA == false){
		$numcl	= $xTabla->clave_numerica()->v();
		$xCL	= new cCajaLocal();
		$xCL->add($xTabla->nombre_sucursal()->v(), $numcl, false, $clave, $xTabla->codigo_postal()->v(), $xTabla->localidad()->v(), $xTabla->estado()->v(), $xTabla->municipio()->v() );
		//Actualizar la Sucursal a donde debe ser Caja Local
		$idcl	= $xCL->getClave();
		$xQL->setRawQuery("UPDATE `general_sucursales` SET `caja_local_residente`=$idcl WHERE `codigo_sucursal`='$clave'");
	} else {
		//compara la sucursal de la caja local con la sucursal nueva
		$xCL	= new cCajaLocal($xTabla->caja_local_residente()->v());
		if($xCL->init() == true){
			if($xCL->getSucursal() !== $clave){
				$xCL	= new cCajaLocal();
				$xCL->add($xTabla->nombre_sucursal()->v(), false, false, $clave, $xTabla->codigo_postal()->v(), $xTabla->localidad()->v(), $xTabla->estado()->v(), $xTabla->municipio()->v() );
				//Actualizar la Sucursal a donde debe ser Caja Local
				$idcl	= $xCL->getClave();
				$xQL->setRawQuery("UPDATE `general_sucursales` SET `caja_local_residente`=$idcl WHERE `codigo_sucursal`='$clave'");
			}
			
		}
	}
} else if($action == MQL_MOD){		//Modificar
	//iniciar
	//$clave 		= parametro($xTabla->getKey(), null, MQL_RAW);
	
	if($clave !== ""){
		$xTabla->setData( $xTabla->query()->initByID($clave));
		$xTabla->setData($_REQUEST);
		$xTabla->codigo_sucursal( $clave );
		//modificar la parte de personas asociadas
		$xTabla->gerente_sucursal($gerente);
		$xTabla->titular_de_cumplimiento($cumplimiento);
		$xTabla->clave_de_persona($persona);
		
		if($ODom === null){
			$xTabla->calle( EACP_DOMICILIO_CALLE );
			$xTabla->codigo_postal( EACP_CODIGO_POSTAL );
			$xTabla->colonia( EACP_COLONIA );
			$xTabla->telefono( EACP_TELEFONO_PRINCIPAL );
			$xTabla->municipio( EACP_MUNICIPIO );
			$xTabla->localidad( EACP_CLAVE_DE_LOCALIDAD );
			$xTabla->estado( EACP_ESTADO );
			$xTabla->numero_exterior( EACP_DOMICILIO_NUM_EXT );
			$xTabla->numero_interior( EACP_DOMICILIO_NUM_INT );
		} else {
			
			$xTabla->calle( $ODom->getCalle() );
			$xTabla->codigo_postal( $ODom->getCodigoPostal() );
			$xTabla->colonia( $ODom->getColonia() );
			$xTabla->telefono( $xSoc->getTelefonoPrincipal() );
			$xTabla->municipio( $ODom->getMunicipio() );
			$xTabla->localidad( $ODom->getClaveDeLocalidad() );
			$xTabla->estado( $ODom->getEstado() );
			$xTabla->numero_exterior( $ODom->getNumeroExterior() );
			$xTabla->numero_interior( $ODom->getNumeroInterior() );
		}				
		$xTabla->query()->update()->save($clave);
		$xFRM->addAvisoRegistroOK();
		$xFRM->addCerrar();
	}
} else {
	$xFRM->addGuardar();
	$xFRM->addPersonaBasico("", false, $xTabla->clave_de_persona()->v(), "", "TR.Persona Vinculada");
	if($step == MQL_MOD){
		$xFRM->ODisabled_13("idxsucursal", $xTabla->codigo_sucursal()->v(), "TR.codigo sucursal");
		$xFRM->OHidden("codigo_sucursal", $clave);
		
	} else {
		$xFRM->OText("codigo_sucursal", $xTabla->codigo_sucursal()->v(), "TR.codigo sucursal");
	}
	
	$xFRM->OText("nombre_sucursal", $xTabla->nombre_sucursal()->v(), "TR.nombre de sucursal");
	$xFRM->OMoneda("clave_numerica", $xTabla->clave_numerica()->v(), "TR.clave en numero");
	//$xFRM->OMoneda("caja_local_residente", , "TR.caja_local", );
	$xFRM->addHElem( $xSel->getListaDeCajasLocales("caja_local_residente", false, $xTabla->caja_local_residente()->v() )->get(true));
	//$xFRM->addPersonaBasico("2", false, $xTabla->gerente_sucursal()->v(), "", "TR.gerente_de_sucursal");
	//$xFRM->addPersonaBasico("3", false, $xTabla->titular_de_cumplimiento()->v(), "", "TR.Oficial_de_Cumplimiento");
	$xFRM->addHElem( $xSel->getListaDeUsuarios("gerente_sucursal", $xTabla->gerente_sucursal()->v())->get("TR.GERENTE_DE_SUCURSAL", true)) ;
	$xFRM->addHElem( $xSel->getListaDeUsuarios("titular_de_cumplimiento", $xTabla->titular_de_cumplimiento()->v())->get("TR.OFICIAL_DE_CUMPLIMIENTO", true));
	
	$xFRM->addHElem($xSel->getListaDeHoras("hora_de_inicio_de_operaciones", $xTabla->hora_de_inicio_de_operaciones()->v(), true)->get("TR.hora de inicio de operaciones", true) );
	$xFRM->addHElem( $xSel->getListaDeHoras("hora_de_fin_de_operaciones", $xTabla->hora_de_fin_de_operaciones()->v(), true)->get("TR.hora cierre de operaciones", true));
	$xFRM->addHElem($xSel->getListaDeCentroDeCostoCont("centro_de_costo", $xTabla->centro_de_costo()->v())->get(true));
	
	$xFRM->OButton("TR.AGREGAR PERSONA", "jsAgregarPersonaNueva()", $xFRM->ic()->PERSONA, "add_new_persona", "persona");
	
	if($step !== MQL_MOD){
		//Lista de Sucursales
		$xT	= new cTabla($xLi->getListadoDeSucursales());
		$xT->setEventKey("jsModificar");
		$xFRM->addHTML( $xT->Show() );
	}	
	
	$xFRM->OHidden("mail", EACP_MAIL);
	$xFRM->OHidden("tel", EACP_TELEFONO_PRINCIPAL);
	
}


echo $xFRM->get();
?>
<script>
var xG	= new Gen();
var xP	= new PersGen();


function jsModificar(sucursal){
	xG.go({url: "frmtipos/sucursales.frm.php?id=" + sucursal})
}

function jsAgregarPersonaNueva(){
	
	
	var tel			= $("#tel").val();
	var mail		= $("#mail").val();
	var nombres		= $("#nombre_sucursal").val();

	xP.goToAgregarMorales({nombre:nombres,tipoingreso:Configuracion.personas.tipoingreso.otros,telefono:tel,email:mail});
}


</script>
<?php
//$jxc ->drawJavaScript(false, true);
$xHP->fin();
?>