<?php
include("php/dbconnect.php");
include("php/checklogin.php");

$sucursal=$_SESSION['rainbow_sucursal'];
if($_GET['type']=="feesearch")
{
$aColumns = array( 's.idRegistro','s.Cod_Estu','s.sname','s.balance','s.fees','c.carrera','s.contact','s.joindate','s.fecha_reg');
	
	/* Indexed column (used for fast and accurate table cardinality) */
	$sIndexColumn = "s.idRegistro";
	
	/* DB table to use */
	$sTable = " student as s, carrera as c ";
	
	
	
	/* 
	 * Paging
	 */
	$sLimit = "";
	if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
	{
		$sLimit = "LIMIT ".mysqli_real_escape_string($conn,$_GET['iDisplayStart'] ).", ".
			mysqli_real_escape_string($conn, $_GET['iDisplayLength'] );

			
	}
	
	
	/*
	 * Ordering
	 */
	 $sOrder = "";
	if ( isset( $_GET['iSortCol_0'] ) )
	{
		$sOrder = "ORDER BY s.fecha_reg DESC ";
		/*for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
		{
			if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
			{
				$sOrder .= $aColumns[ intval( $_GET['iSortCol_'.$i] ) ]."
				 	".mysqli_real_escape_string($conn, $_GET['sSortDir_'.$i] ) .", ";
			}
		}
		
		$sOrder = substr_replace( $sOrder, "", -2 );
		if ( $sOrder == "ORDER BY" )
		{
			$sOrder = "";
		}*/
	}
	
	$cond = "";
	$condArr = array();
	if(isset($_GET['student']) && $_GET['student']!="")
	{
	$condArr[] = "s.sname like '%".mysqli_real_escape_string($conn,$_GET['student'])."%'";
	
	}
	
	if(isset($_GET['carrera']) && $_GET['carrera']!="")
	{
	$condArr[] = "c.id = '".mysqli_real_escape_string($conn,$_GET['carrera'])."'";
	
	}
	
	
	if(isset($_GET['doj']) && $_GET['doj']!="")
	{
	$Adate= explode(' ',$_GET['doj']);
    $month = $Adate[0];
    $year = $Adate[1];
	$months = array('January'=>'01','February'=>'02','March'=>'03','April'=>'04','May'=>'05','June'=>'06','July'=>'07','August'=>'08','September'=>'09','October'=>'10','November'=>'11','December'=>'12');
	
	$doj = $months[$month].'-'.$year;	
	$condArr[] = " DATE_FORMAT(s.joindate, '%m-%Y') = '".$doj."'";
	
	}
	if(count($condArr)>0)
	{
	$cond = " and ( ".implode(" and ",$condArr)." )";
	}
	 
	$mycount = count($aColumns);
	 
	$sWhere = " WHERE c.id=s.carrera and s.delete_status='0' and c.sucursal_varchar='$sucursal' and s.sucursal_varchar='$sucursal'";
	if ( isset($_GET['sSearch'])&& $_GET['sSearch'] != "" )
	{
	    
		$sWhere = $sWhere." and (";
		for ( $i=0 ; $i<$mycount ; $i++ )
		{
		    
			$sWhere .= $aColumns[$i]." LIKE '%".mysqli_real_escape_string($conn, $_GET['sSearch'] )."%' OR ";
		}
		$sWhere = substr_replace( $sWhere, "", -3 );
		$sWhere .= ')';
	}
	
	
	
	/*
	 * SQL queries
	 * Get data to display
	 */
	$sQuery = "
		SELECT SQL_CALC_FOUND_ROWS   ".implode(", ", $aColumns)."
		FROM   ".$sTable."	".$sWhere.$cond." ".$sOrder." ".$sLimit;


	
	$rResult = $conn->query($sQuery) or die(mysqli_error($conn));
	
	/* Data set length after filtering */
	$sQuery = "
		SELECT FOUND_ROWS() as rr
	";
	$rResultFilterTotal = $conn->query( $sQuery) or die(mysqli_error($conn));
	$aResultFilterTotal = $rResultFilterTotal->fetch_assoc();
	$iFilteredTotal = $aResultFilterTotal['rr'];
	
	/* Total data set length */
	$sQuery = "SELECT COUNT(".$sIndexColumn.") as cc
		FROM   ".$sTable." WHERE c.id=s.carrera and s.delete_status='0'   ";
	$rResultTotal = $conn->query( $sQuery ) or die(mysqli_error($conn));
	$aResultTotal = $rResultTotal->fetch_assoc();
	$iTotal = $aResultTotal['cc'];
	
	
	/*
	 * Output
	 */
	 
	if(isset($_GET['sEcho'])) 
	{
	$output = array(
		"sEcho" => intval($_GET['sEcho']),
		"iTotalRecords" => $iTotal,
		"iTotalDisplayRecords" => $iFilteredTotal,
		"aaData" => array()
	);
	}else
	{
	 $output = array(
		
		"iTotalRecords" => $iTotal,
		"iTotalDisplayRecords" => $iFilteredTotal,
		"aaData" => array()
	);
	
	}
	
     $row =array();
	while ( $aRow = $rResult->fetch_assoc()  )
	{
		
		
		$row = array(
                    html_entity_decode($aRow['sname'].'<br/>'.$aRow['contact']),
                    $aRow['fees'],
					$aRow['balance'],
                    $aRow['carrera'],
					date("d M y", strtotime($aRow['joindate'])),
                    
					html_entity_decode('<button class="btn btn-warning btn-xs" onclick="javascript:GetFeeForm('.$aRow['Cod_Estu'].')"><i class="fa fa-usd "></i>  Tomar Pago </button>
					<button class="btn btn-success btn-xs" onclick="javascript:GetFeeFormOtros('.$aRow['Cod_Estu'].')"><i class="fa fa-usd "></i>  Otros Pagos </button> ')

										
                );
		
		$output['aaData'][] =$row;
		
	}
	
	echo json_encode( $output );

}



if($_GET['type']=="report")
{
$aColumns = array( 's.idRegistro','s.Cod_Estu','s.sname','s.balance','s.fees','c.carrera','s.contact','s.joindate','s.fecha_reg');
	
	/* Indexed column (used for fast and accurate table cardinality) */
	$sIndexColumn = "s.idRegistro";
	
	/* DB table to use */
	$sTable = " student as s,carrera as c ";
	
	
	
	/* 
	 * Paging
	 */
	$sLimit = "";
	if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
	{
		$sLimit = "LIMIT ".mysqli_real_escape_string($conn,$_GET['iDisplayStart'] ).", ".
			mysqli_real_escape_string($conn, $_GET['iDisplayLength'] );
	}
	
	
	/*
	 * Ordering
	 */
	 $sOrder = "";
	if ( isset( $_GET['iSortCol_0'] ) )
	{
		$sOrder = "ORDER BY  s.fecha_reg DESC";
		/*for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
		{
			if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
			{
				$sOrder .= $aColumns[ intval( $_GET['iSortCol_'.$i] ) ]."
				 	".mysqli_real_escape_string($conn, $_GET['sSortDir_'.$i] ) .", ";
			}
		}
		
		$sOrder = substr_replace( $sOrder, "", -2 );
		if ( $sOrder == "ORDER BY" )
		{
			$sOrder = "";
		}*/
	}
	
	$cond = "";
	$condArr = array();
	if(isset($_GET['student']) && $_GET['student']!="")
	{
	$condArr[] = "s.sname like '%".mysqli_real_escape_string($conn,$_GET['student'])."%'";
	
	}
	
	if(isset($_GET['carrera']) && $_GET['carrera']!="")
	{
	$condArr[] = "c.id = '".mysqli_real_escape_string($conn,$_GET['carrera'])."'";
	
	}
	
	
	if(isset($_GET['doj']) && $_GET['doj']!="")
	{
	$Adate= explode(' ',$_GET['doj']);
    $month = $Adate[0];
    $year = $Adate[1];
	$months = array('January'=>'01','February'=>'02','March'=>'03','April'=>'04','May'=>'05','June'=>'06','July'=>'07','August'=>'08','September'=>'09','October'=>'10','November'=>'11','December'=>'12');
	
	$doj = $months[$month].'-'.$year;	
	$condArr[] = " DATE_FORMAT(s.joindate, '%m-%Y') = '".$doj."'";
	
	}
	if(count($condArr)>0)
	{
	$cond = " and ( ".implode(" and ",$condArr)." )";
	}
	 
	$mycount = count($aColumns);
	 
	$sWhere = " WHERE c.id=s.carrera and s.delete_status='0' and c.sucursal_varchar='$sucursal' and s.sucursal_varchar='$sucursal' ";
	if ( isset($_GET['sSearch'])&& $_GET['sSearch'] != "" )
	{
	    
		$sWhere = $sWhere." and (";
		for ( $i=0 ; $i<$mycount ; $i++ )
		{
		    
			$sWhere .= $aColumns[$i]." LIKE '%".mysqli_real_escape_string($conn, $_GET['sSearch'] )."%' OR ";
		}
		$sWhere = substr_replace( $sWhere, "", -3 );
		$sWhere .= ')';
	}
	
	/* Individual column filtering 
	for ( $i=0 ; $i<count($aColumns) ; $i++ )
	{
		if ( $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' )
		{
			if ( $sWhere == "" )
			{
				$sWhere = "WHERE ";
			}
			else
			{
				$sWhere .= " AND ";
			}
			$sWhere .= $aColumns[$i]." LIKE '%".mysqli_real_escape_string($conn,$_GET['sSearch_'.$i])."%' ";
		}
	}*/
	
	
	/*
	 * SQL queries
	 * Get data to display
	 */
	$sQuery = "
		SELECT SQL_CALC_FOUND_ROWS   ".implode(", ", $aColumns)."
		FROM   ".$sTable."	".$sWhere.$cond." ".$sOrder." ".$sLimit;
	
	$rResult = $conn->query($sQuery) or die(mysqli_error($conn));
	
	/* Data set length after filtering */
	$sQuery = "
		SELECT FOUND_ROWS() as rr
	";
	$rResultFilterTotal = $conn->query( $sQuery) or die(mysqli_error($conn));
	$aResultFilterTotal = $rResultFilterTotal->fetch_assoc();
	$iFilteredTotal = $aResultFilterTotal['rr'];
	
	/* Total data set length */
	$sQuery = "SELECT COUNT(".$sIndexColumn.") as cc
		FROM   ".$sTable." WHERE c.id=s.carrera and s.delete_status='0'   ";
	$rResultTotal = $conn->query( $sQuery ) or die(mysqli_error($conn));
	$aResultTotal = $rResultTotal->fetch_assoc();
	
	
	/*
	 * Output
	 */
	 
	if(isset($_GET['sEcho'])) 
	{
	$output = array(
		"sEcho" => intval($_GET['sEcho']),
		"iTotalRecords" => $iTotal,
		"iTotalDisplayRecords" => $iFilteredTotal,
		"aaData" => array()
	);
	}else
	{
	 $output = array(
		
		"iTotalRecords" => $iTotal,
		"iTotalDisplayRecords" => $iFilteredTotal,
		"aaData" => array()
	);
	
	}
	
     $row =array();
	while ( $aRow = $rResult->fetch_assoc()  )
	{
		
		
		$row = array(
                    html_entity_decode($aRow['sname'].'<br/>'.$aRow['contact']),
                    $aRow['fees'],
					$aRow['balance'],
                    $aRow['carrera'],
					date("d M y", strtotime($aRow['joindate'])),
                    
					html_entity_decode('<button class="btn btn-warning btn-xs" onclick="javascript:GetFeeForm('.$aRow['Cod_Estu'].')"><i class="fa fa-usd "></i> Ver Detalle de Pago </button>
						<a href="php/HistorialEstudiante.php?req=2&student='.$aRow['Cod_Estu'].'" target="_blank" class="btn btn-primary "><span class="glyphicon .glyphicon-print">Imprimir</span></a>')
										
                );
		
		$output['aaData'][] =$row;
		
	}
	
	echo json_encode( $output );

}

?>