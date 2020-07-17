<?php
/* vehicle-maint-ajax-page
 * Ajax Routines for vehicle-maint
 * BG 07 Apr 2017
 *
 * See C:\Users\Barry\Documents\_dev\ef-docs\_documentation\_ef\guide\maintProgs\EditFlagCode.doc for working with the editFlag functions
 */



function fieldMultiUpload($argSku, $argCol, $argLabel = '', $argClasses = '')
{


    global $connAhq, $control;


    $colName = capFirstLetter($argCol);


    //echo $colName;

    //echo "Debug: ".basename(__FILE__)." Line ". __LINE__."<br>";



    if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || $_SERVER['REQUEST_SCHEME'] == 'https') {
        $tmp_Protocol = 'https';
    } else {
        $tmp_Protocol = 'http';
    }

    $tmp_DocPath = $tmp_Protocol . "://" . $control['clientDomain'] . "/clients/" . $_SESSION['clientID'] . "-" . $_SESSION['clientKey'] . "/docs/";



    switch ($argCol) {

        case 'v5c':
            $tmp_ShortDocType = 'V';
            break;

        case 'serviceHistDoc':
            $tmp_ShortDocType = 'S';
            break;

        default:
            die("Error: Unrecognised column '" . $argCol . "' - Execution ceased in " . basename(__FILE__) . " Line " . __LINE__ . $newLine);

            break;
    }


    $query_rsMotorDoc = "
		SELECT * FROM " . $control['dbName'] . ".motordoc 
		WHERE sku='" . $argSku . "' AND docType='" . $tmp_ShortDocType . "' AND status!='X';
	";
    //echo $query_rsMotorDoc."<br>";
    $rsMotorDoc = ahqSql_query($query_rsMotorDoc, $connAhq) or mysql_fail_ef();
    $totalRows_rsMotorDoc = mysqli_num_rows($rsMotorDoc);
    //echo $totalRows_rsMotorDoc." Rows<br>";



?>

    <div class="col-md-<?php if ($argLabel == 'noLabel') echo 12;
                        else echo 6; ?><?php echo " " . trim($argClasses); ?>">
        <div class="inputCell">

            <?php if (isset($tmp_FileName)) { ?>

                <a href="<?php echo $tmp_DocPath . $tmp_FileName; ?>" class="btn btn-xs" target="_blank">View <?= ($colName == 'Vc5' ? 'V5C' : $colName) ?></a>

            <?php } else { ?>

                <form id="upload<?= $colName ?>" action="" method="post" enctype="multipart/form-data">
                    <div class="form-row uploadOuter" id="<?= $colName ?>Cont">
                        <?php if ($argLabel != 'noLabel') { ?><label><?= ($colName == 'Vc5' ? 'V5C' : $colName) ?>:</label><br /><?php } ?>
                        <input type="file" name="file[]" id="file" class="form-control-file border" accept="application/pdf, image/jpg, image/jpeg" multiple>
                        <button class="btn btn-xs" onclick="clickSpin(this);">Upload</button>
                    </div>
                </form>

                <p>
                    There are <?= $totalRows_rsMotorDoc ?> documents stored
                    <?php if ($totalRows_rsMotorDoc > 0) { ?>
                        <a data-toggle="modal" href="#modal-support" onclick="viewDocs('<?= $argSku ?>', '<?= $argCol ?>');">
                            <span class="menu-title">View</span>
                        </a>
                    <?php } ?>
                </p>

                <?php // Note in the 4th arg below that we have substituted # for * as its not easy to url encode: 
                ?>
                <script>
                    $("#AdminPanel #upload<?= $colName ?>").on('submit', (function(e) {
                        e.preventDefault();

                        savePostForm(this, 'vehicle-maint-ajax', 'save<?= $colName ?>', 'AdminPanel *<?= $colName ?>Cont', '<?php echo $argSku; ?>', '<?php echo "showAdminData"; //$clean['action'];
                                                                                                                                                    ?>');
                    }));
                </script>

            <?php } ?>

        </div>
    </div>

    <?php





}



if (!isset($displayAction) || $displayAction == '')
    die('<b>Error:</b> ' . $clean['page'] . '-page (12) - Required displayAction parameter missing');

//echo "Processing displayAction: ".$displayAction."<br>";


switch ($displayAction) {

    case 'showTopData':
    ?>
        <div class="col-md-12">
            <div class="box">
                <div class="box-header d-flex justify-content-between">

                    <div class="title bolder2" id="prodTitle">
                        <?php echo $Motor->getSku(); ?><?php if ($Motor->getType() != 'C') { ?> [<?php echo removeTrailingEss($ahqGlobal['motorTypeName'][$Motor->getType()]); ?>]<?php } ?>:
                        &nbsp;&nbsp;<?php echo $Motor->getMake(); ?> <?php echo $Motor->getModel(); ?> <?php echo $Motor->getVariant(); ?> <?php echo $Motor->getReg(); ?>
                        <input id="stockCode" type="hidden" value="<?php echo $Motor->getSku(); ?>">
                    </div>

                    <div class="box-link d-flex">
                        <ul class="box-toolbar">
                            <li class="toolbar-link">

                                <?php if (!isset($sysM_dms['3pSite']) || $sysM_dms['3pSite'] != 'Y') { // AHQ Sites 
                                ?>
                                    <a href="<?php echo buildAhqUrl(['sku' => $Motor->getSku(), 'type' => $Motor->getType(), 'make' => $Motor->getMake(), 'model' => $Motor->getModel(), 'variant' => $Motor->getVariant()], 'car'); ?>" target="_blank">
                                        <i class="mdi mdi-earth"></i>
                                        <span>Visit</span> Web <span>Page</span>
                                    </a>
                                <?php } else if ((isset($sysM_dms['3pSite']) || $sysM_dms['3pSite'] != 'Y') && $MotorInfo->getUrl() != '') { // 3rd Party Sites 
                                ?>
                                    <a href="<?php echo $MotorInfo->getUrl(); ?>" target="_blank">
                                        <i class="mdi mdi-earth"></i>
                                        <span>Visit</span> Web <span>Page</span>
                                    </a>
                                <?php } else { ?>
                                    <div class="noLink">
                                        <i class="mdi mdi-earth"></i>
                                        No Web URL
                                    </div>
                                <?php } ?>

                            </li>
                        </ul>
                        <?php if ($Motor->getStatus() < 'B' && $User->getPrivValue('I')) { ?>
                            <ul class="box-toolbar">
                                <li class="toolbar-link">
                                    <a data-toggle="modal" href="#modal-support" onclick="receiptModal('<?php echo $clean['sku']; ?>');">
                                        Receive <i class="fas fa-warehouse"></i>
                                    </a>
                                </li>
                            </ul>


                        <?php } else if ($Motor->getStatus() == 'B10' && $User->getPrivValue('I')) { ?>
                            <ul class="box-toolbar">
                                <li class="toolbar-link">
                                    <a href="#" onclick="startReadyForSale('<?php echo $clean['sku']; ?>')">
                                        Make <span> Ready for Sale</span> <i class="fas fa-tag"></i>
                                    </a>
                                </li>
                            </ul>
                        <?php } ?>




                        <?php /* } else if($Motor->getStatus()=='B10') { ?>
					<ul class="box-toolbar">
						<li class="toolbar-link">
							<a href="#" onclick="popupContinue('PDI Passed?', 'Click Continue to approve or click Back to abort.', 'pdiApprove(\'<?php echo $clean['sku'];?>\')', 'doNothing()')" >
							PDI<span> Pass</span> <i class="fas fa-clipboard-check"></i>
							</a>
						</li>
					</ul>
					<?php } */ ?>


                        <?php if ($essentialDataRequired) { ?>
                            <ul class="box-toolbar">
                                <li class="toolbar-link">
                                    <a data-toggle="modal" href="#modal-support" onclick="essentialModal('<?php echo $clean['sku']; ?>');">
                                        Essential <span>Data</span> <i class="fas fa-exclamation-triangle"></i></a>
                                </li>
                            </ul>
                        <?php } ?>

                        <ul class="box-toolbar">
                            <li class="toolbar-link highlight">
                                <a href="<?php echo $row_rsPage['arg']; ?>">Close <i class="mdi mdi-close"></i></a>
                            </li>
                        </ul>
                    </div>

                </div>
                <!--box-header-->
            </div>
            <!--box-->
        </div>
        <!--md-->

    <?php
        break;


    case 'showBasicData':
        require_once($siteDir_Class . "/EssentialModal.php");
        $EssentialModal = new EssentialModal();
    ?>

        <div class="row">
            <div class="col-md-6">
                <div class="box">

                    <div class="box-content">
                        <table class="table table-striped">

                            <?php if ($control['clientID'] == 1109) { // Try this just on Nicholson initially (Build 123)
                            ?>
                                <tr>
                                    <td>Condition:</td>
                                    <td>
                                        <select id="condition" onChange="setEditFlag('<?php echo $displayAction; ?>')">
                                            <option value="Y" <?php if ($Motor->getUsed() == 'Y') echo "selected"; ?>>Used</option>
                                            <option value="E" <?php if ($Motor->getUsed() == 'E') echo "selected"; ?>>Ex Demo</option>
                                            <option value="N" <?php if ($Motor->getUsed() == 'N') echo "selected"; ?>>New</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php } else { ?>
                                <input type="hidden" id="condition" value="Y">
                            <?php } ?>


                            <tr>
                                <td>Registration: </td>
                                <td>
                                    <!-- <?php echo $Motor->getType(); ?> -->
                                    <input id="reg" <?php if ($Motor->getType() != 'C' && $Motor->getType() != 'M' && $Motor->getType() != 'R' && $Motor->getType() != 'E') echo "disabled "; ?>type="text" maxlength="70" value="<?php echo $Motor->getReg(); ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                            <tr>
                                <td>VIN Number: </td>
                                <td>
                                    <input id="vinNum" type="text" maxlength="70" value="<?php echo $Motor->getVinNum(); ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />
                                </td>
                            </tr>

                            <tr>
                                <td>Make: </td>
                                <td>
                                    <input id="make" type="text" maxlength="70" value="<?php echo $Motor->getMake(); ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />
                                </td>
                            </tr>

                            <tr>
                                <td>Model: </td>
                                <td>
                                    <input id="model" type="text" maxlength="70" value="<?php echo $Motor->getModel(); ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />
                                </td>
                            </tr>

                            <tr>
                                <td>Variant: </td>
                                <td>
                                    <input id="variant" type="text" maxlength="70" value="<?php echo $Motor->getVariant(); ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                            <tr>
                                <td>Manufacturers Colour: </td>
                                <td>
                                    <input id="manuColour" type="text" <?php if ($Motor->getType() != 'C' && $Motor->getType() != 'V' && $Motor->getType() != 'M' && $Motor->getType() != 'T') echo "disabled "; ?>maxlength="70" value="<?php echo $Motor->getManuColour(); ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                            <tr>
                                <td>Basic Colour: </td>
                                <td>
                                    <input id="colour" type="text" maxlength="70" value="<?php echo $Motor->getColour(); ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                            <tr>
                                <td>Number of <?php echo ($Motor->getType() == 'T' ? 'Births' : 'Doors'); ?>:</td>
                                <td>
                                    <input id="numDoors" type="text" <?php if ($Motor->getType() != 'C' && $Motor->getType() != 'T') echo "disabled "; ?>maxlength="70" value="<?php echo $Motor->getNumDoors(); ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                            <tr>
                                <td>Registration Date: </td>
                                <td>
                                    <input id="regDate" type="text" maxlength="70" value="<?php echo SQL_to_UK_Date($Motor->getRegDate()); ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                    <script>
                                        $('#regDate').datepicker({
                                            orientation: 'top left',
                                            dateFormat: "dd-mm-yy",
                                            todayBtn: "linked",
                                            showButtonPanel: true,
                                            firstDay: 1,
                                            autoclose: true,
                                            todayHighlight: true
                                        });
                                        //renderTimePicker('<?php echo SQL_to_UK_Date($Motor->getRegDate()); ?>');
                                    </script>
                                </td>
                            </tr>




                        </table>
                    </div>
                    <!--box-content-->

                </div>
                <!--box-->
            </div>
            <!--col-->

            <?php
            // The next three drop downs apply to vehicle types C, M, R, E, D
            // Distil this into one tmp var
            switch ($Motor->getType()) {

                case 'C':
                case 'M':
                case 'R':
                case 'E':
                case 'D':
                    $tmp_NonMotor = false;
                    break;

                default:
                    $tmp_NonMotor = true;
            }

            // Certain Vehicle Types don't have a category of bodyType
            if ($Motor->getType() == 'E' || $Motor->getType() == 'L')
                $tmp_SingleBodyOptions = true;
            else
                $tmp_SingleBodyOptions = false;
            ?>

            <div class="col-md-6">
                <div class="box">

                    <div class="box-content">
                        <table class="table table-striped">


                            <tr>
                                <td><?php if ($Motor->getType() == 'C' || $Motor->getType() == 'V') echo "Body Type";
                                    else echo "Category"; ?>:</td>
                                <td>
                                    <?php

                                    /* Motor.types that have no bodyTypes:
										 * T - Caravan
										 * M - Motorhome
										 * L - Trailers
										 * E - Telescopic Handlers
										 * B - Motorbike
										 */

                                    if (strstr("TMLEB", $Motor->getType()))
                                        echo "n/a";
                                    else
                                        Motor::selectBodyType($Motor->getBodyType(), $Motor->getType(), $tmp_SingleBodyOptions);
                                    ?>
                                </td>
                            </tr>

                            <tr>
                                <td>Fuel Type:</td>
                                <td><?php Motor::selectFuelType($Motor->getFuelType(), $tmp_NonMotor); ?></td>
                            </tr>

                            <tr>
                                <td>Transmission:</td>
                                <td><?php Motor::selectTransmission($Motor->getTransmission(), $tmp_NonMotor); ?></td>
                            </tr>

                            <tr>
                                <td>Litres: </td>
                                <td>
                                    <input id="engineLitres" type="text" maxlength="70" value="<?php echo $Motor->getEngineLitres(); ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                            <tr>
                                <td><?php if ($Motor->getType() == 'C' || $Motor->getType() == 'V' || $Motor->getType() == 'M') echo 'Mileage';
                                    else if ($Motor->getType() == 'H') echo 'Bale Count';
                                    else echo 'Hours'; ?>:</td>
                                <td class="mileageSection">
                                    <input id="mileage" type="text" maxlength="70" value="<?php echo $Motor->getMileage(); ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />
                                    <button type="button" class="btn btn-xs" onClick="mileageHistory('<?= $clean['sku'] ?>')">
                                        <i class="fas fa-history"></i>
                                    </button>
                                </td>
                            </tr>

                            <?php /*
                            <tr>
                                <td>Location:</td>
                                <td>
                                    <input type="text" value="<?php echo $Motor->getGarageID();?>" id="storeID">
									<?php
                                    //require_once($siteDir_Class."/Store.php");
                                    //Store::StoreChooser($connAhq, $sysM_motor['defaultGarageID'], $Motor->getGarageID(), "storeID", "refreshServicePicker(this.value)", 'excludeClosed');
                                    ?>
                                </td>
                            </tr>
							*/ ?>

                            <tr>
                                <td>Status:</td>
                                <td>
                                    <?php
                                    $query_rsStatus = "
										SELECT * FROM a0_common.names WHERE type='motorStatus' 
										AND status='N' 
										AND code!='T' ORDER BY screenSeq
									";
                                    $rsStatus = ahqSql_query($query_rsStatus, $connAhq) or mysql_fail_ef();
                                    //echo $query_rsStatus."<br>";
                                    ?>

                                    <?php if ($control['dmsFull'] != 'Y' && $Motor->getStatus() != 'Z') { ?>

                                        <select id="status" onChange="setEditFlag('<?php echo $displayAction; ?>')">
                                            <?php while ($row_rsStatus = mysqli_fetch_assoc($rsStatus)) {
                                                if ($row_rsStatus['code'] == 'S70') $row_rsStatus['name'] = 'Sold';
                                                if ($row_rsStatus['code'] == 'A20') $row_rsStatus['name'] = 'Due in';
                                            ?>
                                                <option value="<?php echo $row_rsStatus['code']; ?>" <?php if ($Motor->getStatus() == $row_rsStatus['code']) echo "selected"; ?>><?php echo $row_rsStatus['name']; ?></option>
                                            <?php } ?>
                                        </select>

                                    <?php } else { ?>

                                        <input type="hidden" id="status" value="<?php echo $Motor->getStatus(); ?>">

                                        <p><?php echo $Motor->getStatusText(); ?></p>

                                    <?php } ?>


                                </td>
                            </tr>

                            <tr>
                                <td>Visibility in Website: </td>
                                <td>
                                    <?php
                                    if ($Motor->getStatus()[0] < 'S')
                                        $EssentialModal->InputControls('single', 'showInWebsite', 'noLabel');
                                    else
                                        echo "n/a";
                                    ?>
                                    <input type="hidden" id="showInWebsite" value="<?= $Motor->getShowInWebsite() ?>">
                                    <?php
                                    ?>
                                </td>
                            </tr>


                            <tr>
                                <td>Featured:</td>
                                <td>
                                    <select id="featured" onChange="setEditFlag('<?php echo $displayAction; ?>')">
                                        <option value="Y" <?php if ($Motor->getFeatured() == 'Y') echo "selected"; ?>>Yes</option>
                                        <option value="N" <?php if ($Motor->getFeatured() == 'N' || $Motor->getFeatured() == '') echo "selected"; ?>>No</option>
                                    </select>
                                </td>
                            </tr>

                            <?php if (true) { ?>
                                <tr>
                                    <td>VED (12 months) [Beta]</td>
                                    <td><?php echo $Motor->getVed12Months(); ?></td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <td>Clearance:</td>
                                <td>
                                    <select id="clearance" onChange="setEditFlag('<?php echo $displayAction; ?>')">
                                        <option value="Y" <?php if ($Motor->getClearance() == 'Y') echo "selected"; ?>>Yes</option>
                                        <option value="N" <?php if ($Motor->getClearance() == 'N' || $Motor->getClearance() == '') echo "selected"; ?>>No</option>
                                    </select>
                                </td>

                            </tr>
                        </table>
                    </div>
                    <!--box-content-->





                </div>
                <!--box-->


                <div class="btnCont">
                    <input type="hidden" value="<?php echo $Motor->getGarageID(); ?>" id="storeID">
                    <a tabindex="0" class="btn btn-lg btn-blue" href="#" onclick="exit('01000010');"><i class="icon-angle-left"></i> Cancel</a>
                    <a tabindex="0" class="btn btn-lg btn-red" href="#" onclick="saveBasicData('<?php echo $clean['sku']; ?>')"><i class="icon-check"></i> Save</a>
                </div>

            </div>
            <!--col-->

        </div>
        <!--row-->

        <script>
            updTabs('Basic', '<?php echo $clean['sku']; ?>');
        </script>


        <?php //if(isset($_SESSION['fileUploaded'])) { 
        ?>
        <script>
            //anprWizStep1('<?php echo $clean['sku']; ?>');
            //$("#modal-form").modal('show');
        </script>
        <?php //} 
        ?>

    <?php
        break;



    case 'showPricingData':
    ?>

        <div class="row">
            <div class="col-md-5">
                <div class="box">

                    <div class="box-header">
                        <div class="title bolder2">Current Prices</div>
                    </div>

                    <div class="box-content">


                        <table class="table table-striped">

                            <tr>
                                <td>Price: </td>
                                <td>
                                    <?php if ($User->getPrivValue('I')) { ?>
                                        <input id="price" type="text" maxlength="10" class="right" value="<?= number_format($Motor->getPrice(), 2, '.', '') ?>" onChange="updateMargin('<?= $clean['sku'] ?>'); setEditFlag('<?php echo $displayAction; ?>')" />
                                    <?php } else { ?>
                                        <div class="uneditable right"><?= number_format($Motor->getPrice(), 2, '.', '') ?></div>
                                        <input id="price" type="hidden" value="<?= $Motor->getPrice() ?>" />
                                    <?php } ?>
                                </td>

                                <td>
                                    <?php if ($control['dmsFull'] == 'Y' && $User->getPrivValue('X')) { ?>
                                        <span id="margCalc">

                                        </span>
                                    <?php } ?>
                                </td>

                            </tr>

                            <tr>
                                <td>Was Price: </td>
                                <td>
                                    <?php if ($User->getPrivValue('I')) { ?>
                                        <input id="wasPrice" type="text" maxlength="12" class="right" value="<?php echo number_format($Motor->getWasPrice(), 2, '.', ''); ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />
                                    <?php } else { ?>
                                        <div class="uneditable right"><?= number_format($Motor->getWasPrice(), 2, '.', '') ?></div>
                                        <input id="wasPrice" type="hidden" value="<?= $Motor->getWasPrice() ?>" />
                                    <?php } ?>
                                </td>
                                <?php if ($control['dmsFull'] == 'Y') { ?>
                                    <td>&nbsp;</td>
                                <?php } ?>
                            </tr>


                            <?php if ($control['dmsFull'] == 'Y') { ?>

                                <tr>
                                    <td>Purchase Cost:</td>
                                    <td>
                                        <?php if ($User->getPrivValue('I')) { ?>
                                            <input id="cost" type="text" maxlength="12" class="right" value="<?php echo number_format($Motor->getCost(), 2, '.', ''); ?>" style="text-align: left;" onChange="updateMargin('<?= $clean['sku'] ?>'); setEditFlag('<?php echo $displayAction; ?>')" />
                                        <?php } else { ?>
                                            <?php if ($User->getPrivValue('X')) { ?>
                                                <div class="uneditable right"><?= number_format($Motor->getCost(), 2, '.', '') ?></div>
                                            <?php } ?>
                                            <input id="cost" type="hidden" value="<?= $Motor->getCost() ?>" />
                                        <?php } ?>
                                    </td>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr>
                                    <td>Expenses: </td>
                                    <td>
                                        <?php if ($User->getPrivValue('X')) { ?>
                                            <div class="uneditable right"><?= number_format($row_rsExpenses['cost']) ?></div>
                                        <?php } ?>
                                    </td>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr>
                                    <td>Total Cost: </td>
                                    <td>
                                        <?php if ($User->getPrivValue('X')) { ?>
                                            <div class="uneditable right"><?= number_format($Motor->getCost() + $row_rsExpenses['cost']) ?></div>
                                        <?php } ?>
                                        <input id="totalCost" type="hidden" value="<?= $Motor->getCost() + $row_rsExpenses['cost'] ?>" />
                                    </td>
                                    <td>&nbsp;</td>
                                </tr>


                                <tr>
                                    <td>VAT: </td>
                                    <td>
                                        <?php if ($User->getPrivValue('I')) { ?>
                                            <select id="vatCode" onChange="updateMargin('<?= $clean['sku'] ?>');">
                                                <?php while ($row_rsVat = mysqli_fetch_assoc($rsVat)) { ?>
                                                    <option value="<?php echo $row_rsVat['code']; ?>" <?php if ($Motor->getVatCode() == $row_rsVat['code']) echo " selected"; ?>>
                                                        <?php echo $row_rsVat['description']; ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        <?php } else { ?>
                                            <div class="uneditable right"><?= $Motor->getVatCode() ?></div>
                                            <input id="vatCode" type="hidden" value="<?= $Motor->getVatCode() ?>" />
                                        <?php } ?>
                                    </td>
                                    <td>&nbsp;</td>
                                </tr>
                            <?php } else { ?>
                                <tr>
                                    <td>VAT: </td>
                                    <td>
                                        <select id="vatCode">
                                            <?php while ($row_rsVat = mysqli_fetch_assoc($rsVat)) { ?>
                                                <option value="<?php echo $row_rsVat['code']; ?>" <?php if ($Motor->getVatCode() == $row_rsVat['code']) echo " selected"; ?>><?php echo ($row_rsVat['code'] == 'M' ? 'Vat Inclusive' : '+ Vat Pricing'); ?></option>
                                            <?php } ?>
                                        </select>
                                    </td>
                                    <td>&nbsp;</td>
                                </tr>
                            <?php } ?>


                        </table>
                    </div>
                    <!--box-content-->


                </div>
                <!--box-->

                <div class="right">
                    <?php if ($User->getPrivValue('I')) { ?>
                        <a tabindex="0" class="btn btn-lg btn-red" href="#" onclick="savePricingData('<?= $clean['sku'] ?>')"><i class="icon-check"></i> Save</a>
                    <?php } ?>
                </div>

            </div>
            <!--col-->

            <div class="col-md-7">


                <div class="box">

                    <div class="box-header">
                        <div class="title bolder2">Pricing History</div>
                    </div>

                    <div class="box-content">


                        <table class="table table-striped">

                            <tr>
                                <th>Timestamp</th>
                                <?php if ($User->getPrivValue('X') === true) { ?>
                                    <th>Cost</th>
                                <?php } ?>
                                <th>Retail</th>
                                <th>VatCode</th>
                                <th>User</th>
                            </tr>

                            <?php while ($row_rsPrice = mysqli_fetch_assoc($rsPrice)) { ?>
                                <tr>
                                    <td><?= sqlTimeStamp_to_UK_Date_Time($row_rsPrice['timeStamp']) ?></td>
                                    <?php if ($User->getPrivValue('X') === true) { ?>
                                        <td class="right"><?= ($row_rsPrice['cost'] == '' ? '' : number_format($row_rsPrice['cost'], 2)) ?></td>
                                    <?php } ?>
                                    <td class="right"><?= ($row_rsPrice['retail'] == '' ? '' : number_format($row_rsPrice['retail'], 2)) ?></td>
                                    <td class="centre"><?= $row_rsPrice['vatCode'] ?></td>
                                    <td class="centre"><?= $row_rsPrice['userName'] ?></td>
                                </tr>
                            <?php } ?>

                        </table>

                    </div>
                    <!--box-content-->


                </div>
                <!--box-->


            </div>
            <!--col-->

        </div>
        <!--row-->

        <script>
            updTabs('Pricing', '<?php echo $clean['sku']; ?>');
            updateMargin('<?= $clean['sku'] ?>')
        </script>
    <?php
        break;



    case 'showDescData':
    ?>

        <div class="row">
            <div class="col-md-12">
                <div class="box">

                    <div class="box-content">
                        <table class="table table-striped showDescData">

                            <tr>
                                <td class="title">Title: </td>
                                <td>
                                    <input id="title" type="text" maxlength="70" value="<?php echo $Motor->getCombinedTitle(); ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />
                                </td>
                            </tr>

                            <tr>
                                <td>Headline: </td>
                                <td>
                                    <input id="headline" type="text" maxlength="<?php if ($control['clientID'] == 1105) echo '70';
                                                                                else echo '30'; ?>" value="<?php echo htmlentities($MotorInfo->getHeadline()); ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />
                                </td>
                            </tr>

                            <tr>
                                <td>Bullets<br>(Put each bullet on a new line)</td>
                                <td>
                                    <textarea id="description2"><?php echo $MotorInfo->getDescription2(); ?></textarea>
                                </td>
                            </tr>

                            <tr>
                                <td>Description: </td>
                                <td>
                                    <textarea id="description3"><?php echo $MotorInfo->getDescription3(); ?></textarea>
                                </td>
                            </tr>

                            <tr>
                                <td>Options &amp; Upgrades<br>(Comma-separated list)</td>
                                <td>
                                    <textarea id="upgrades"><?php echo $MotorInfo->getUpgrades(); ?></textarea>
                                    <?php /*<input type="hidden" id="upgrades" value="<?php echo $MotorInfo->getUpgrades();?>">*/ ?>
                                </td>
                            </tr>

                            <?php if ($control['clientID'] == 1106) { ?>
                                <tr>
                                    <td>Closing Text: </td>
                                    <td>
                                        <textarea id="description4"><?php echo $MotorInfo->getDescription4(); ?></textarea>
                                    </td>
                                </tr>
                            <?php } ?>

                        </table>
                        <?php if ($control['clientID'] != 1106) { ?>
                            <input type="hidden" id="description4" value="<?php echo $MotorInfo->getDescription4(); ?>">
                        <?php } ?>



                    </div>
                    <!--box-content-->


                </div>
                <!--box-->

                <div class="right">
                    <a tabindex="0" class="btn btn-lg btn-blue" href="#" onclick="exit('01000010');"><i class="icon-angle-left"></i> Cancel</a>
                    <a tabindex="0" class="btn btn-lg btn-red" href="#" onclick="saveDescData('<?php echo $clean['sku']; ?>')"><i class="icon-check"></i> Save</a>
                </div>

            </div>
            <!--col-->
        </div>
        <!--row-->

        <script>
            updTabs('Desc', '<?php echo $clean['sku']; ?>');
        </script>
    <?php
        break;




    case 'showTechData':
    ?>

        <div class="row technical">



            <div class="col-lg-4 col-md-6 col-xs-12">
                <div class="box">

                    <div class="box-header">
                        <div class="title bolder2">Performance</div>
                    </div>

                    <div class="box-content">
                        <table class="table table-striped">

                            <tr>
                                <td>BHP: </td>
                                <td>
                                    <input id="bhp" type="text" maxlength="70" value="<?php echo $MotorInfo->getBhp(); ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                            <tr>
                                <td>Kw: </td>
                                <td>
                                    <input id="kw" type="text" maxlength="70" value="<?php echo $MotorInfo->getKw();; ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                            <tr>
                                <td>Acceleration: </td>
                                <td>
                                    <input id="acceleration" type="text" maxlength="70" value="<?php echo $MotorInfo->getAcceleration();; ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                            <tr>
                                <td>Max Mph: </td>
                                <td>
                                    <input id="maxMph" type="text" maxlength="70" value="<?php echo $MotorInfo->getMaxMph();; ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                            <tr>
                                <td>Number of Gears: </td>
                                <td>
                                    <input id="numGears" type="text" maxlength="70" value="<?php echo $MotorInfo->getNumGears();; ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                            <tr>
                                <td>Number of Cylinders: </td>
                                <td>
                                    <input id="numCylinders" type="text" maxlength="70" value="<?php echo $MotorInfo->getNumCylinders();; ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                            <tr>
                                <td>Number of Valves: </td>
                                <td>
                                    <input id="numValves" type="text" maxlength="70" value="<?php echo $MotorInfo->getNumValves();; ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>


                            <tr>
                                <td>Engine CCs: </td>
                                <td>
                                    <input id="engineCcs" type="text" maxlength="70" value="<?php echo $MotorInfo->getEngineCcs();; ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>


                            <tr>
                                <td>Cylinder Layout: </td>
                                <td>
                                    <input id="cylinderLayout" type="text" maxlength="70" value="<?php echo $MotorInfo->getCylinderLayout();; ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                        </table>
                    </div>
                    <!--box-content-->


                </div>
                <!--box-->
            </div>
            <!--col-->

            <div class="col-lg-4 col-md-6 col-xs-12">

                <div class="box">

                    <div class="box-header">
                        <div class="title bolder2">Efficiency & Emmissions</div>
                    </div>

                    <div class="box-content">
                        <table class="table table-striped">

                            <tr>
                                <td>Std Euro Emissions: </td>
                                <td>
                                    <input id="stdEuroEmissions" type="text" maxlength="70" value="<?php echo $MotorInfo->getStdEuroEmissions(); ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                            <tr>
                                <td>CO2: </td>
                                <td>
                                    <input id="co2" type="text" maxlength="70" value="<?php echo $MotorInfo->getCo2();; ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                            <tr>
                                <td>Urban Mpg: </td>
                                <td>
                                    <input id="urbanMpg" type="text" maxlength="70" value="<?php echo $MotorInfo->getUrbanMpg();; ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                            <tr>
                                <td>Extra Urban Mpg: </td>
                                <td>
                                    <input id="xUrbanMpg" type="text" maxlength="70" value="<?php echo $MotorInfo->getExtraUrbanMpg();; ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                            <tr>
                                <td>Combi Mpg: </td>
                                <td>
                                    <input id="combiMpg" type="text" maxlength="70" value="<?php echo $MotorInfo->getCombiMpg();; ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                        </table>
                    </div>
                    <!--box-content-->
                </div>
                <!--box-->

            </div>
            <!--col-->



            <div class="col-lg-4 col-md-6 col-xs-12">
                <div class="box">

                    <div class="box-header">
                        <div class="title bolder2">Sizes & Capacities</div>
                    </div>

                    <div class="box-content">

                        <table class="table table-striped">

                            <tr>
                                <td>Length: </td>
                                <td>
                                    <input id="length" type="text" maxlength="70" value="<?php echo $MotorInfo->getLength(); ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                            <tr>
                                <td>Width: </td>
                                <td>
                                    <input id="width" type="text" maxlength="70" value="<?php echo $MotorInfo->getWidth();; ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                            <tr>
                                <td>Height: </td>
                                <td>
                                    <input id="height" type="text" maxlength="70" value="<?php echo $MotorInfo->getHeight();; ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                            <tr>
                                <td>Wheelbase: </td>
                                <td>
                                    <input id="wheelbase" type="text" maxlength="70" value="<?php echo $MotorInfo->getWheelbase();; ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>


                            <tr>
                                <td>GVW: </td>
                                <td>
                                    <input id="gvw" type="text" maxlength="70" value="<?php echo $MotorInfo->getGvw();; ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>


                            <tr>
                                <td>Min Kerb Weight: </td>
                                <td>
                                    <input id="minKerbWeight" type="text" maxlength="70" value="<?php echo $MotorInfo->getMinKerbWeight();; ?>" style="text-align: left;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />

                                </td>
                            </tr>

                        </table>
                    </div>
                    <!--box-content-->


                </div>
                <!--box-->


            </div>
            <!--col-->



        </div>
        <!--row-->

        <div class="row">

            <div class="col-xs-12">

                <div class="right">
                    <a tabindex="0" class="btn btn-lg btn-blue" href="#" onclick="exit('01000010');"><i class="icon-angle-left"></i> Cancel</a>
                    <a tabindex="0" class="btn btn-lg btn-red" href="#" onclick="saveSizeData('<?php echo $clean['sku']; ?>')"><i class="icon-check"></i> Save</a>
                </div>

            </div>
        </div>
        <script>
            updTabs('Tech', '<?php echo $clean['sku']; ?>');
        </script>

    <?php
        break;


    case 'showStdEquipData':
    ?>
        <div class="stdEquipData">
            <?php echo $MotorInfo->getDescription1(); ?>
        </div>
    <?php
        break;



    case 'showAdminData':

        require_once($siteDir_Class . "/EssentialModal.php");
        $EssentialModal = new EssentialModal();

    ?>
        <div class="row">
            <div class="col-md-12">

                <div class="box">

                    <div class="box-content">

                        <form id="showAdmin" action="" method="post" enctype="multipart/form-data">

                            <table class="table table-striped">
                                <tr>
                                    <td class="label">Company: </td>
                                    <td>
                                        <?php $EssentialModal->InputControls('single', 'compID', 'noLabel'); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Sale or Return: </td>
                                    <td>
                                        <p class="textOnly">
                                            <?php if (isset($SorCust)) { ?>
                                                Yes: (<?= $SorCust->getFirstName() . ' ' . $SorCust->getSurname() ?> - <?= $Motor->getSorCustID() ?>)
                                            <?php } else { ?>
                                                No
                                            <?php }  ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Supplier Condition Report: </td>
                                    <td id="conditionRptOuter">
                                        <?php $EssentialModal->InputControls('single', 'conditionRpt', 'noLabel'); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label">V5C Cert: </td>
                                    <td id="v5cOuter">
                                        <?php //$EssentialModal->InputControls('single', 'v5c', 'noLabel');
                                        ?>
                                        <?php fieldMultiUpload($clean['sku'], 'v5c', 'noLabel'); ?>
                                    </td>
                                </tr>

                                <?php /*
							<tr>
								<td class="label">V5C Cert (Inner): </td>
								<td id="v5cInnerOuter">
									<?php $EssentialModal->InputControls('single', 'v5cInner', 'noLabel');?>
								</td>
							</tr>
							*/ ?>

                                <tr>
                                    <td>MOT Cert: </td>
                                    <td id="motCertOuter">
                                        <?php $EssentialModal->InputControls('single', 'motCert', 'noLabel'); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Service History: </td>
                                    <td id="serviceHistDocOuter">
                                        <?php //$EssentialModal->InputControls('single', 'serviceHistDoc', 'noLabel');
                                        ?>
                                        <?php fieldMultiUpload($clean['sku'], 'serviceHistDoc', 'noLabel'); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>PDI Report: </td>
                                    <td id="pdiReportOuter">
                                        <?php $EssentialModal->InputControls('single', 'pdiReport', 'noLabel'); ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="label">Supplier ID: </td>
                                    <td>
                                        <?php $EssentialModal->InputControls('single', 'supplierID', 'noLabel'); ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Funder ID: </td>
                                    <td>
                                        <?php $EssentialModal->InputControls('single', 'funderID', 'noLabel'); ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Preparation Budget: </td>
                                    <td>
                                        <?php $EssentialModal->InputControls('single', 'prepBudget', 'noLabel'); ?>
                                    </td>
                                </tr>


                                <tr>
                                    <td>Key Safe No.: </td>
                                    <td>
                                        <?php $EssentialModal->InputControls('single', 'keySafeNum', 'noLabel'); ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Number of Keys: </td>
                                    <td>
                                        <?php $EssentialModal->InputControls('single', 'numberOfKeys', 'noLabel'); ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td>Location: </td>
                                    <td>
                                        <?php $EssentialModal->InputControls('single', 'garageID', 'noLabel'); ?>
                                    </td>
                                </tr>
                                <?php /* 
							<tr>
								<td>PDI Passed: </td>
								<td>
									<?php $EssentialModal->InputControls('single', 'pdiPass', 'noLabel');?>
								</td>
							</tr>
							*/ ?>
                            </table>

                            <div class="btnCont right">
                                <a tabindex="0" class="btn btn-lg" href="#" onclick="exit('01000010');"><i class="icon-angle-left"></i> Cancel</a>
                                <input type="submit" class="btn btn-lg">
                            </div>

                        </form>

                    </div>
                    <!--box-content-->

                </div>
                <!--box-->

            </div>
            <!--col-->

        </div>
        <!--row-->

        <script>
            updTabs('Admin', '<?php echo $clean['sku']; ?>');

            $("form#showAdmin").on('submit', (function(e) {
                savePostForm(this, 'vehicle-maint-ajax', 'saveAdminData', 'ajaxOutput', '<?php echo $Motor->getSku(); ?>');
                e.preventDefault();
            }));
        </script>

    <?php
        break;



    case 'showAttribData':
    ?>
        <div class="row">
            <div class="col-md-12">
                <div class="box">
                    <div class="box-header">
                        <div class="title bolder2">Selected Attributes</div>
                    </div>
                    <!--box-header-->
                    <div class="box-content" id="attribPreview<?php if (isset($clean['location'])) echo $clean['location']; ?>"></div>
                    <!--box-content-->
                </div>
                <!--box-->
            </div>
            <!--col-->
        </div>
        <!--row-->

        <div class="row">
            <div class="col-sm-12">
                <div class="box">

                    <div class="box-header">
                        <div class="title bolder2">Add an Attribute</div>
                    </div>
                    <!--box-header-->

                    <div class="box-content">
                        <table class="table table-striped">

                            <tr>
                                <td>Group:</td>
                                <td>
                                    <?php if ($totalRows_rsAttribHead > 0) { ?>
                                        <select id="attribHead" onChange="populateAttribLines('<?php if (isset($clean['location'])) echo $clean['location']; ?>')">
                                            <?php while ($row_rsAttribHead = mysqli_fetch_assoc($rsAttribHead)) { ?>
                                                <option value="<?php echo $row_rsAttribHead['attribHeadID']; ?>"><?php echo $row_rsAttribHead['description']; ?></option>
                                            <?php } ?>
                                        </select>
                                    <?php } else { ?>
                                        No Further Attributes Available
                                    <?php } ?>
                                </td>
                                <td>&nbsp;</td>
                            </tr>

                            <?php if ($totalRows_rsAttribHead > 0) { ?>
                                <tr>
                                    <td>Item:</td>
                                    <td>
                                        <div id="attribLineCon<?php if (isset($clean['location'])) echo $clean['location']; ?>">
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-red btn-md" onClick="addAttrib('<?php echo $clean['sku']; ?>', '<?php echo $clean['location']; ?>')" ;>Add</button>
                                    </td>
                                </tr>
                            <?php } ?>

                        </table>
                    </div>
                    <!--box-content-->

                </div>
                <!--box-->
            </div>
            <!--col-->
        </div>
        <!--row-->



        <script>
            refreshFeatureAttribs('<?php echo $clean['sku']; ?>', '<?php echo $clean['location']; ?>');

            <?php if ($totalRows_rsAttribHead > 0) { ?>
                populateAttribLines();
            <?php } ?>

            <?php if (isset($clean['location'])) { ?>
                refreshFeatureAttribs('<?php echo $clean['sku']; ?>', '<?php echo $clean['location']; ?>');
                <?php if ($totalRows_rsAttribHead > 0) { ?>
                    populateAttribLines('<?php echo $clean['location']; ?>');
                <?php } ?>
            <?php } ?>
        </script>

        <?php
        break;

    case 'refreshFeatureAttribs':

        if ($totalRows_rsAttrib > 1) {
            $tmp_lastHeading = '';
            while ($row_rsAttrib = mysqli_fetch_assoc($rsAttrib)) {
        ?>
                <?php if ($tmp_lastHeading != '' && $row_rsAttrib['heading'] != $tmp_lastHeading) { ?>
                    </ul>
                    </div>

                <?php } ?>

                <?php if ($row_rsAttrib['heading'] != $tmp_lastHeading) { ?>

                    <div class="attribBlock">
                        <h3><?php echo $row_rsAttrib['heading']; ?></h3>
                        <ul>
                        <?php } ?>

                        <li><?php echo $row_rsAttrib['value']; ?> <a href="#" onClick="delAttrib('<?php echo $row_rsAttrib['motorAttribID']; ?>', '<?php echo $clean['location']; ?>')"><i class="fa fa-times-circle highlightColor" aria-hidden="true"></i></a></li>

                <?php
                $tmp_lastHeading = $row_rsAttrib['heading'];

                //arrayDump($row_rsAttrib);
            }
        } else {
            echo "No attribs yet";
        }
                ?>

                        </ul>
                    </div>

                    <?php
                    break;


                case 'populateAttribLines':
                    if ($row_rsAttribHead['open'] == 'Y') {
                    ?>
                        <input type="text" id="attribLine<?php if (isset($clean['location'])) echo $clean['location']; ?>" maxlength="50">
                    <?php
                    } else {
                    ?>
                        <select id="attribLine<?php if (isset($clean['location'])) echo $clean['location']; ?>">
                            <?php while ($row_rsAttribLines = mysqli_fetch_assoc($rsAttribLines)) { ?>
                                <option value="<?php echo $row_rsAttribLines['value']; ?>"><?php echo $row_rsAttribLines['value']; ?></option>
                            <?php } ?>
                        </select>
                    <?php
                    }
                    break;


                case 'delAttrib':
                case 'addAttrib':
                    ?>
                    <script>
                        if ('<?php echo $clean['location']; ?>' == 'Modal')
                            showAttribsInEssentials('<?php echo $clean['sku']; ?>');

                        showData('Attrib', '<?php echo $clean['sku']; ?>', 'release');
                    </script>
                <?php
                    break;



                case 'showLogsData':
                ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box">


                                <div class="box-content">

                                    <table class="table table-striped">
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>Event</th>
                                            <th>Narrative</th>
                                            <th>User</th>
                                        </tr>
                                        <?php
                                        $count = 0;
                                        while ($row_rsLog = mysqli_fetch_assoc($rsLog)) {
                                        ?>
                                            <tr>
                                                <td class="row<?php echo $count % 2; ?>"><?php echo sqlTimeStampToLocalDateOrTime($row_rsLog['eventTime']); ?></td>
                                                <td class="row<?php echo $count % 2; ?>"><?php echo $row_rsLog['eventName']; ?></td>
                                                <td class="row<?php echo $count % 2; ?>"><?php echo $row_rsLog['narrative']; ?></td>
                                                <td class="row<?php echo $count % 2; ?>"><?php echo $row_rsLog['userID']; ?> (<?php echo $row_rsLog['userName']; ?>)</td>
                                            </tr>
                                        <?php
                                            $count++;
                                        } ?>

                                    </table>
                                </div>
                                <!--box-content-->


                            </div>
                            <!--box-->
                        </div>
                        <!--col-->


                    </div>
                    <!--row-->



                    <?php
                    require_once($siteDir_Inc . '/garage-url-funcs.php');
                    require_once($siteDir_Class . "/Motor.php");
                    $Motor = new Motor();
                    //$Motor->setSku($clean['sku']);
                    if (!$Motor->dbRead($connAhq, $clean['sku'], 'sku'))
                        dieerror("This SKU does not exist in the database");

                    ?>
                    <a href="<?php echo buildAhqUrl(['sku' => $Motor->getSku(), 'type' => $Motor->getType(), 'make' => $Motor->getMake(), 'model' => $Motor->getModel(), 'variant' => $Motor->getVariant()], 'car'); ?>" target="_blank">?</a>

                <?php


                    break;


                case 'showPrepData':

                    require_once($siteDir_Class . "/EssentialModal.php");
                    $EssentialModal = new EssentialModal();

                    if (date('U') - SQL_Date_to_PHP_TimeStamp($Motor->getMotExpiry()) > 0)
                        $expired = true;
                    else
                        $expired = false;

                    $tmp_DaysRegd =  sqlDateDiff(today_mySQL(), $Motor->getRegDate());
                    //echo $tmp_DaysRegd." days registered<br>";

                ?>
                    <div class="row">
                        <div class="col-md-12">

                            <div class="alert <?php echo (($expired || $Motor->getMotlastResult() == 'F') ? "alert-danger" : "alert-success"); ?>">
                                <b>MOT Status:</b><br>

                                <?php if ($tmp_DaysRegd < (365 * 3)) { ?>
                                    Car is less than 3 years old and does not require an MOT yet (registered <?= SQL_to_UK_DateTime($Motor->getRegDate()) ?>
                                <?php } else { ?>
                                    <i class="fas fa-check-circle"></i> MOT Expires: <?= SQL_to_UK_Date($Motor->getMotExpiry()) ?>
                                    <?php if ($Motor->getMotlastResult() == 'F') { ?>** Last Test was a Failure **<br><?php } ?>
                                (Status was last checked <?= SQL_to_UK_Date($Motor->getMotLookupTime()) ?>)
                                - [<a href="#" onClick="checkMotStatus('<?= $clean['sku'] ?>')">Check Latest MOT Status</a>]
                            <?php } ?>
                            </div>

                        </div>
                        <!--col-->
                    </div>
                    <!--row-->


                    <div class="row">
                        <div class="col-md-12">
                            <div class="box">

                                <div class="box-content">

                                    <table class="table table-striped showWorksData">

                                        <thead>
                                            <tr>
                                                <th>Date Added</th>
                                                <th>Customer</th>
                                                <th>Category</th>
                                                <th class="longer">Narrative</th>
                                                <th class="shorter">Cost</th>
                                                <th class="shorter">Added By</th>
                                                <th class="iconCol">&nbsp;</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php
                                            $totalEstCost = 0;
                                            while ($row_rsWork = mysqli_fetch_assoc($rsWork)) {
                                            ?>
                                                <tr>
                                                    <td><?= SQL_to_UK_Date($row_rsWork['createDate']) ?></td>
                                                    <td>
                                                        <?php
                                                        if ($row_rsWork['soID'] != '')
                                                            echo "Order: " . $row_rsWork['soID'] . " - ";
                                                        else if ($row_rsWork['enqID'] != '')
                                                            echo "Enquiry: " . $row_rsWork['enqID'] . " - ";
                                                        echo $row_rsWork['custName'];
                                                        ?>
                                                    </td>
                                                    <td><?= $row_rsWork['catName'] ?></td>
                                                    <td><?= $row_rsWork['narrative'] ?></td>
                                                    <td><?php if ($row_rsWork['userID'] != 1) echo $row_rsWork['fullName'] ?></td>
                                                    <td class="right"><?= number_format($row_rsWork['estCost'], 2) ?></td>
                                                    <td>
                                                        <?php if ($row_rsWork['status'] == 'C') { ?>
                                                            <i class="fas fa-check-square colour04"></i>&nbsp;Completed
                                                        <?php } else { ?>
                                                            <a href="#" onclick="popupContinue('Delete this Job?', 'Click Continue to delete it or click Back to abort.', 'delJob(<?= $row_rsWork['ID'] ?>)', 'doNothing()')" title="Delete Job">
                                                                <i class="far fa-trash-alt highlightColor"></i>
                                                            </a>
                                                            &nbsp;
                                                            <a data-toggle="modal" href="#modal-support" onclick="editJob(<?= $row_rsWork['ID'] ?>)">
                                                                <i class="far fa-edit"></i>
                                                            </a>
                                                        <?php } ?>
                                                    </td>
                                                </tr>
                                            <?php
                                                $totalEstCost += $row_rsWork['estCost'];
                                            }
                                            ?>
                                            <tr class="totals">
                                                <td>&nbsp;</td>
                                                <td>&nbsp;</td>
                                                <td>Total</td>
                                                <td class="right"><?php echo number_format($totalEstCost, 2); ?></td>
                                                <td class="iconCol">&nbsp;</td>
                                            </tr>

                                            <tr id="worksActions">
                                                <td colspan="7">
                                                    <a href="#" class="btn" onclick="addJob('<?php echo $clean['sku']; ?>');">
                                                        <i class="mdi mdi-plus-circle"></i> Add Works
                                                    </a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>


                                </div>
                                <!--box-content-->

                            </div>
                            <!--box-->
                        </div>
                        <!--col-->
                    </div>
                    <!--row-->

                    <div class="row">
                        <div class="col-md-12">
                            <div class="box">
                                <div class="box-content">
                                    <table class="table table-striped">
                                        <tr>
                                            <td>PDI Inspection Report: </td>
                                            <td id="pdiReportOuter">
                                                <?php $EssentialModal->InputControls('single', 'pdiReport', 'noLabel'); ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <!--box-content-->
                            </div>
                            <!--box-->
                        </div>
                        <!--col-->
                    </div>
                    <!--row-->

                    <script>
                        updTabs('Prep', '<?php echo $clean['sku']; ?>');
                    </script>
                <?php
                    break;


                case 'addJob':
                ?>
                    <th>New Entry:</th>
                    <td>
                        <?php
                        $sysM_workCat = paramGroup($control['dbName'] . '.masparam', 'workCat', $connAhq);
                        ?>
                        <div class="col-md-6">
                            <div class="inputCell">

                                <select id="catID">
                                    <option>Please Select</option>
                                    <?php foreach ($sysM_workCat as $key => $value) { ?>
                                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                    <?php } ?>
                                </select>

                            </div>
                        </div>
                    </td>
                    <td class="longer">
                        <textarea id="narrative"></textarea>
                    </td>
                    <td class="shorter right" colspan="2">
                        <input id="estCost" type="text" maxlength="70" />
                        <a href="#" class="btn" onclick="saveJob('<?php echo $clean['sku']; ?>');">
                            <i class="mdi mdi-plus-circle"></i> Save
                        </a>
                    </td>
                <?php
                    break;



                case 'editJob':
                ?>
                    <div class="modal-header">
                        <h4 class="titleBlock modal-title">
                            Edit Job
                            <button type="button" class="close" data-dismiss="modal" onclick="$('#diaryBooking').css('visibility', 'hidden')">×</button>
                        </h4>
                    </div>

                    <div class="modal-body">

                        <div class="row">

                            <?php $sysM_workCat = paramGroup($control['dbName'] . '.masparam', 'workCat', $connAhq); ?>
                            <div class="col-md-12">
                                <label for="">Category:</label><br>
                                <select id="catID">
                                    <option>Please Select</option>
                                    <?php foreach ($sysM_workCat as $key => $value) { ?>
                                        <option value="<?php echo $key; ?>" <?php if ($key == $MotorWork->getCategoryID()) echo " selected"; ?>><?= $value ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label for="">Description:</label><br>
                                <textarea id="narrative"><?= $MotorWork->getNarrative() ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label for="">Cost:</label><br>
                                <input type="text" placeholder="Event Title" id="estCost" value="<?= $MotorWork->getEstCost() ?>" maxlength="30"><br>
                            </div>
                            <div class="col-md-6">
                                <label for="defaultUnchecked">Job Completed</label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="completed">
                                    <label class="custom-control-label" for="completed">&nbsp</label>
                                </div>
                            </div>

                        </div>

                        <div class="row buttons">
                            <div class="col-md-12">
                                <a href="#" data-dismiss="modal" class="btn btn-lg">Cancel</a>
                                <a href="javascript:updateJob(<?= $clean['ID'] ?>)" class="btn btn-lg">Save</a>
                            </div>
                        </div>


                    </div>

                    <div class="bookingFooter">
                    </div>


                <?php
                    break;


                case 'saveJob':
                case 'delJob':
                case 'updateJob':
                case 'checkMotStatus':
                ?>
                    <script>
                        var page = $("#page").html();
                        var enqID = $("#enqID").html();

                        if (page == 'vehicle-maint') {
                            showData('Prep', "<?php echo $clean['sku']; ?>", 'release');
                            $("#modal-support").modal("hide");
                        } else if (page == 'showroom') {
                            showData(enqID, 'Prep')
                        }
                    </script>
                <?php
                    break;




                case 'updTabs':
                ?>
                    <div class="nav nav-tabs nav-tabs-left">
                        <a <?php if ($clean['tab'] == 'Basic') echo 'class="active" '; ?>href="#BasicPanel" data-toggle="tab" onClick="checkEditFlag('showData(\'Basic\', \'<?php echo $clean['sku']; ?>\', \'release\')')">Basic Data</a>
                        <a <?php if ($clean['tab'] == 'Pricing') echo 'class="active" '; ?>href="#PricingPanel" data-toggle="tab" onClick="checkEditFlag('showData(\'Pricing\', \'<?php echo $clean['sku']; ?>\', \'release\')')">Pricing</a>
                        <a <?php if ($clean['tab'] == 'Image') echo 'class="active" '; ?>href="#ImagePanel" data-toggle="tab" onClick="checkEditFlag('showData(\'Image\', \'<?php echo $clean['sku']; ?>\', \'release\')')">Images</a>
                        <a <?php if ($clean['tab'] == 'Desc') echo 'class="active" '; ?>href="#DescPanel" data-toggle="tab" onClick="checkEditFlag('showData(\'Desc\', \'<?php echo $clean['sku']; ?>\', \'release\')')">Description</a>
                        <a <?php if ($clean['tab'] == 'Tech') echo 'class="active" '; ?>href="#TechPanel" data-toggle="tab" onClick="checkEditFlag('showData(\'Tech\', \'<?php echo $clean['sku']; ?>\', \'release\')')">Technical</a>

                        <?php if ($control['dmsPrep'] == 'Y') { ?>
                            <a <?php if ($clean['tab'] == 'Prep') echo 'class="active" '; ?>href="#PrepPanel" data-toggle="tab" onClick="checkEditFlag('showData(\'Prep\', \'<?php echo $clean['sku']; ?>\', \'release\')')">Preparation</a>
                        <?php } ?>

                        <?php if ($control['dmsFull'] == 'Y') { ?>
                            <a <?php if ($clean['tab'] == 'Admin') echo 'class="active" '; ?>href="#AdminPanel" data-toggle="tab" onClick="checkEditFlag('showData(\'Admin\', \'<?php echo $clean['sku']; ?>\', \'release\')')">Admin</a>
                        <?php } ?>

                        <?php if ($control['dmsFull'] == 'Y' || ClientAttrib::CheckAttrib('dmsEcom', 'Y')) { ?>
                            <a <?php if ($clean['tab'] == 'Info') echo 'class="active" '; ?>href="#InfoPanel" data-toggle="tab" onClick="checkEditFlag('showData(\'Info\', \'<?php echo $clean['sku']; ?>\', \'release\')')">Info</a>
                        <?php } ?>

                        <?php if ($control['clientID'] == 1109) { // Attribs only used on Nicholson at present
                        ?>
                            <li<?php if ($clean['tab'] == 'Attrib') echo ' class="active"'; ?>><a href="#AttribPanel" onClick="checkEditFlag('showData(\'Attrib\', \'<?php echo $clean['sku']; ?>\', \'release\')')" data-toggle="tab">Attributes</a></li>
                            <?php } ?>

                            <?php if ($User->getPrivValue('U')) { ?>
                                <a <?php if ($clean['tab'] == 'Logs') echo 'class="active" '; ?>href="#LogsPanel" data-toggle="tab" onClick="checkEditFlag('showData(\'Logs\', \'<?php echo $clean['sku']; ?>\', \'release\')')">Logs</a>
                            <?php } ?>

                    </div>
                <?php
                    break;


                case 'saveBasicData':
                case 'saveEssentialData':
                case 'saveReceipt':
                ?>
                    <script>
                        //Now the data is saved reset the editFlag
                        document.getElementById('editFlag').innerHTML = 'Clear';
                        //updTabs('Basic', '<?php echo $clean['sku']; ?>');
                        showTopData('<?php echo $clean['sku']; ?>');
                        showData('Basic', '<?php echo $clean['sku']; ?>', 'release');
                        <?php if (!$allOK) { ?>
                            //popup('text', "<?php echo $err_Text; ?>", 'Unable to Delete', 'Alert');
                        <?php } ?>
                        $("#modal-support").modal("hide");
                        ahqAlert('Vehicle Data Updated');
                    </script>
                    <?php
                    break;


                case 'saveV5c':
                case 'saveV5cFront':
                case 'saveV5cInner':
                case 'saveMotCert':
                case 'saveConditionRpt':
                case 'savePdiReport':
                case 'saveServiceHistDoc':

                    $tmp_Action = 'render' . $tmp_DocType;

                    if ($allOK) {
                    ?>
                        <script>
                            vehMaintAction("<?php echo $clean['sku']; ?>", '<?php echo $tmp_Action; ?>', '<?php echo $clean['div']; ?>');
                            ahqAlert('Document Uploaded');
                        </script>
                    <?php
                    } else {
                    ?>
                        <p class="error">
                            <?php echo $tmp_ErrorTxt; ?>
                            <a href="#" onclick="checkEditFlag('showData(\'Admin\', \'U2806\', \'release\')')">
                                &nbsp;[Try again]
                            </a>
                        </p>
                    <?php
                    }

                    break;


                case 'savePricingData':
                    ?>
                    <script>
                        //Now the data is saved reset the editFlag
                        document.getElementById('editFlag').innerHTML = 'Clear';
                        showTopData('<?php echo $clean['sku']; ?>');
                        showData('Pricing', '<?php echo $clean['sku']; ?>', 'release');

                        <?php if ($pricesUpdated) { ?>
                            ahqAlert('Vehicle Pricing Data Updated');
                        <?php } else { ?>
                            ahqAlert('No Price Changes to Update');
                        <?php } ?>
                    </script>
                <?php
                    break;


                case 'saveDescData':
                ?>
                    <script>
                        //Now the data is saved reset the editFlag
                        document.getElementById('editFlag').innerHTML = 'Clear';
                        showTopData('<?php echo $clean['sku']; ?>');
                        showData('Desc', '<?php echo $clean['sku']; ?>', 'release');
                        ahqAlert('Vehicle Data Updated');
                    </script>
                <?php
                    break;


                case 'saveSizeData':
                ?>
                    <script>
                        //Now the data is saved reset the editFlag
                        document.getElementById('editFlag').innerHTML = 'Clear';
                        showTopData('<?php echo $clean['sku']; ?>');
                        showData('Tech', '<?php echo $clean['sku']; ?>', 'release');
                        ahqAlert('Vehicle Data Updated');
                    </script>
                <?php
                    break;


                case 'saveAdminData':
                ?>
                    <script>
                        //Now the data is saved reset the editFlag
                        document.getElementById('editFlag').innerHTML = 'Clear';
                        showTopData('<?php echo $clean['sku']; ?>');
                        showData('Admin', '<?php echo $clean['sku']; ?>', 'release');
                        ahqAlert('Vehicle Data Updated');
                    </script>
                <?php
                    break;


                case 'Locks':
                    break;


                case 'newSku1':
                    require_once($siteDir_Class . "/PayPlan.php");
                ?>
                    <div class="row">
                        <div class="col-md-9">
                            <div class="box">


                                <?php
                                if (PayPlan::AllowMoreProds() === true) {

                                    // max prods Code:
                                    if (!isset($tmp_CreatedTemplateRecords)) {
                                ?>
                                        <div class="box-header">
                                            <div class="title bolder2">Create a SKU</div>
                                        </div>
                                        <div class="box-content">
                                            <table class="table table-striped">
                                                <tr>
                                                    <td style="width:20%">Choose a Department: </td>
                                                    <td>
                                                        <input type="text" id="newSkuDept" onBlur="populateNewSkuDeptName(this.value)" />
                                                        <a href="#" onClick="popupSearch(0, 'deptSubDept', 'populateNewSkuDept', 'jsLauncher', 'filter', 'newSkuDept')" accesskey="F" title="Find">
                                                            <img src="images/search.gif" /></a>
                                                        ( <span id="newSkuDeptName"><?php if (isset($row_rsMotor)) echo $row_rsMotor['parentDeptName'] . ' - ' . $row_rsMotor['subDeptName']; ?></span> )
                                                        <?php echo OnlineHelp::QuickHelp('prod.dept'); ?>
                                                    </td>
                                                    <td style="width:15%">
                                                        <a tabindex="0" class="btn btn-green" onClick="newSku2()">Continue <i class="icon-angle-right"></i></a>
                                                    </td>
                                                </tr>
                                            </table>
                                            <script>
                                                document.getElementById('newSkuDept').focus();
                                            </script>
                                    <?php
                                    } else {
                                        echo $tmp_CreatedTemplateRecords;
                                        echo "The template record defines the default values for new products.  
										You can modify these defaults at any time by editing <a href=\"?page=vehicle-maint&mode=edit&stockCode=" . $tmpTemplateSku . "\">Sku #" . $tmpTemplateSku . "</a>. <br />
										<b>Please <a href=\"?page=vehicle-maint&mode=new\">refresh this page</a> to begin creating new products.</b><br />";
                                    }
                                } else {
                                    include($adminDir_Elem . "/license-notice.php");
                                    licenseNotice('You cannot add further products to this system - you will need to upgrade your package.', 'You are currently limited to ' . PayPlan::AllowMoreProds('limit') . ' products');
                                }

                                    ?>

                                        </div>
                                        <!--box-content-->
                            </div>
                            <!--box-->
                        </div>
                        <!--col-->
                    </div>
                    <!--row-->

                <?php

                    break;


                case 'newSku2':
                ?>
                    <div class="row">
                        <div class="col-md-9">
                            <div class="box">
                                <div class="box-content">
                                    <table class="table table-striped">
                                        <tr>
                                            <td style="width: 20%;">Department: </td>
                                            <td><?php echo $clean['newSkuDept']; ?> (<?php echo $row_rsDept['name']; ?>)</td>
                                            <td style="width:15%">&nbsp;</td>
                                        </tr>
                                        <tr>
                                            <td>Proposed SKU No.: </td>
                                            <td>
                                                <input id="newStockCode" value="<?php echo $tmp_NewSku; ?>" />
                                            </td>
                                            <td>
                                                <a tabindex="0" class="btn btn-green" onClick="insertNewSku('<?php echo $clean['newSkuDept']; ?>')">Create SKU <i class="icon-plus"></i></a>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <!--box-content-->
                            </div>
                            <!--box-->
                        </div>
                        <!--col-->
                    </div>
                    <!--row-->

                <?php
                    break;

                case 'insertNewSku':
                ?>
                    <script>
                        startEdit('<?php echo $clean['newStockCode']; ?>')
                    </script>
                <?php
                    break;



                case 'editComponent':
                ?>
                    <td style="width: 120px;" class="editing" style="width: 70px;">
                        <input id="compStockCode" type="text" maxlength="8" onFocus="preValidateSave(this.value);" onBlur="validateStockCode(this.id, this.value); addCompValStockCode(this.value, '<?php echo $clean['sku']; ?>')" value="<?php if (isset($OfferSet)) echo $OfferSet->getStockCode();
                                                                                                                                                                                                                                            else if (isset($clean['compStockCode'])) echo $clean['compStockCode']; ?>" style="width: 70px!important; text-align: left;">
                        <a href="#" onClick="popupSearch(0, 'prod', 'populateCompStockCode', 'jsLauncher', 'filter', 'compStockCode')" accesskey="F" title="Find"><img src="images/search.gif" /></a>&nbsp;
                        <?php //echo OnlineHelp::QuickHelp('_gen.SkuSearch');
                        ?>
                        <script>
                            document.getElementById('compStockCode').focus();
                        </script>
                    </td>
                    <td class="editing" id="compStockCodeName">&nbsp;</td>
                    <td style="width: 40px;" class="editing right" style="width: 70px;"><input id="qty" type="text" maxlength="6" value="<?php if (isset($OfferSet)) echo $OfferSet->getQty();
                                                                                                                                            else if (isset($clean['qty'])) echo $clean['qty']; ?>" onFocus="preValidateSave(this.value);" onBlur="validate(this.id, this.value, 'int', 999999, 0.01)" style="width: 40px!important; text-align: left; text-align:right!important;"></td>
                    <td style="width: 50px;" class="editing right" style="width: 90px;">&nbsp;</td>
                    <td style="width: 60px;" class="editing right" style="width: 100px;">&nbsp;</td>
                    <td style="width: 20px;" class="editing" style="width: 30px;">&nbsp;</td>
                    <td style="width: 20px;" class="editing" style="width: 30px;">
                        <a href="#" onClick="saveComponent('<?php echo $clean['sku']; ?>', '<?php echo $clean['ID']; ?>', 'update');" title="Update this component"><img src="images/iconMaintSave.gif"></a>
                    </td>
                <?php
                    break;


                case 'addComponent':
                ?>
                    <td class="editing" style="width: 120px;">
                        <input type="text" id="compStockCode" onKeyPress="if (EnterPressed(event)) {enterSku(); return false}" onFocus="preValidateSave(this.value);" onBlur="validateStockCode(this.id, this.value); addCompValStockCode(this.value, '<?php echo $clean['sku']; ?>')" value="<?php if (isset($clean['compStockCode'])) echo $clean['compStockCode']; ?>" style="width: 70px!important;" />
                        <a href="#" onClick="popupSearch(0, 'prod', 'populateCompStockCode', 'jsLauncher', 'filter', 'compStockCode')" accesskey="F" title="Find"><img src="images/search.gif" /></a>&nbsp;
                        <?php echo OnlineHelp::QuickHelp('_gen.SkuSearch'); ?>
                        <script>
                            document.getElementById('compStockCode').focus();
                        </script>
                    </td>
                    <td style="width: 40px;" class="editing" id="compStockCodeName">&nbsp;</td>
                    <td style="width: 50px;" class="editing"><input id="qty" type="text" value="<?php if (isset($clean['qty'])) echo $clean['qty']; ?>" onFocus="preValidateSave(this.value);" onBlur="validate(this.id, this.value, 'float', 999999, 0.01)" style="width: 50px!important; text-align: left;"></td>
                    <td style="width: 60px;" class="editing">&nbsp;</td>
                    <td style="width: 20px;" class="editing">&nbsp;</td>
                    <td style="width: 20px;" class="editing" colspan="2">
                        <a href="#" onClick="saveComponent('<?php echo $clean['sku']; ?>', '', 'insert');" title="Save this component"><img src="images/iconMaintSave.gif"></a>
                    </td>
                <?php
                    break;


                case 'reloadSet':
                ?>
                    <script>
                        showData('Component', '<?php echo $clean['sku']; ?>', '');
                    </script>
                    <?php
                    break;




                case 'renderSuppName':
                    if ($totalRows_rsSupp == 0) {
                    ?>

                        <script>
                            // po-create build 98
                            //validationError('Supplier Not On File', 'Please enter a valid supplierID *', 'suppID', 'populateSuppName');
                        </script>

                    <?php
                    } else {
                        echo $row_rsSupp['name'];
                    }
                    break;


                case 'renderVatCodeName':
                    if ($totalRows_rsVat == 0) {
                    ?>
                        <script>
                            validationError('Vat Code Not On File', 'Please enter a valid purchVatCode', 'purchVatCode', 'populatePurchVatCodeName');
                        </script>
                    <?php
                    } else {
                        echo $row_rsVat['description'];
                    }
                    break;




                case 'renderDeptName':
                    if ($totalRows_rsDept == 0) {
                    ?>
                        <script>
                            validationError('Department Not On File', 'Please enter a valid deptCode', 'deptCode', 'populateDeptName');
                        </script>
                    <?php
                    } else {
                        echo $row_rsDept['name'];
                    }
                    break;


                case 'renderBuyerName':
                    if ($totalRows_rsBuyer == 0) {
                    ?>
                        <script>
                            validationError('Buyer Not On File', 'Please enter a valid buyerID', 'buyerID', 'populateBuyerName');
                        </script>
                    <?php
                    } else {
                        echo $row_rsBuyer['fullName'];
                    }
                    break;



                case 'editStoreData':
                    ?>
                    <input id="storeData" type="text" onBlur="updStoreData('<?php echo $clean['field']; ?>', '<?php echo $clean['storeID']; ?>', '<?php echo $clean['sku']; ?>');" value="<?php echo $clean['qty']; ?>" maxlength="5" />
                    <script>
                        document.getElementById('storeData').select();
                    </script>
                <?php
                    break;


                case 'updStoreData':
                ?>
                    <script>
                        showData('Store', '<?php echo $clean['sku']; ?>', 'release');
                    </script>
                    <?php
                    break;


                case 'showWebData':
                    if ($totalRows_rsWeb == 0) {
                    ?>
                        <div style="margin: 15px;">
                            <p>This product is not on the website <?php echo $masterSiteNames[$_SESSION['siteNum'] - 1]; ?></p>
                            <a href="#" onClick="popupContinue('Add Product to Website?', 'Click Continue to add this item to the current website.  Click back to abort.', 'addToWebsite(\'<?php echo $clean['sku']; ?>\')', 'doNothing()')"><img src="images/add-to-website.gif"></a>
                        </div>
                    <?php
                    } else if ($row_rsWeb['status'] == 'X') { ?>
                        <div style="margin: 15px;">
                            This product has been deleted from this website <?php echo $masterSiteNames[$_SESSION['siteNum'] - 1]; ?>:<br />
                            <a href="#" onClick="popupContinue('Reinstate Product to Website?', 'Click Continue to reinstate this item on the current website.  Click back to abort.', 'reinstateToWebsite(\'<?php echo $clean['sku']; ?>\')', 'doNothing()')">Click Here to Reinstate</a>
                        </div>
                    <?php
                    } else {
                    ?>

                        <div class="row">
                            <div class="col-md-9">
                                <div class="box">
                                    <div class="box-content">
                                        <table class="table table-striped">

                                            <tr>
                                                <td>Web Description:</td>
                                                <td colspan="2">
                                                    <input id="webDesc" type="text" class="extraLong" maxlength="70" value="<?php echo htmlentities($row_rsWeb['description']); ?>" onFocus="preValidateSave(this.value)" onBlur="validate(this.id, this.value, 'text', 70)" onChange="setEditFlag('<?php echo $displayAction; ?>')" />
                                                    <div style="margin: 5px 0;"><strong>URL: </strong><?php echo $row_rsWeb['prodURL']; ?></div>
                                                    <?php // echo OnlineHelp::QuickHelp('prodsite.description');
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Meta Title:</td>
                                                <td colspan="2">
                                                    <input id="metaTitle" type="text" class="extraLong" maxlength="70" value="<?php echo htmlentities($row_rsWeb['metaTitle']); ?>" onFocus="preValidateSave(this.value)" onBlur="validate(this.id, this.value, 'text', 70)" onChange="setEditFlag('<?php echo $displayAction; ?>')" />
                                                    <?php //echo OnlineHelp::QuickHelp('prodsite.description');
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Meta Keywords:</td>
                                                <td colspan="2">
                                                    <input id="metaKeywords" type="text" class="extraLong" maxlength="70" value="<?php echo htmlentities($row_rsWeb['metaKeywords']); ?>" onFocus="preValidateSave(this.value)" onBlur="validate(this.id, this.value, 'text', 70)" onChange="setEditFlag('<?php echo $displayAction; ?>')" />
                                                    <?php //echo OnlineHelp::QuickHelp('prodsite.description');
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Web Category:</td>
                                                <td>
                                                    <input type="text" id="category" size="4" maxlength="4" value="<?php echo $row_rsWeb['category']; ?>" onFocus="preValidateSave(this.value)" onBlur="populateCatName(this.value)" onChange="setEditFlag('<?php echo $displayAction; ?>')" />
                                                    <a href="#" onClick="popupSearch(0, 'category', 'populateCatID', 'jsLauncher', 'all', 'category')" accesskey="F" title="Find">
                                                        <img src="images/search.gif" /></a>&nbsp;
                                                    ( <span id="catName"><?php echo $row_rsWeb['categoryName']; ?></span> )
                                                    <?php echo OnlineHelp::QuickHelp('prodsite.category'); ?>
                                                </td>
                                                <td rowspan="4">
                                                    <a href="#" onclick="loadImageMaint('<?php echo $clean['sku']; ?>')">
                                                        <img src="<?php echo $sysE_tillMaster['prodImagePath'] . getCodedDir('p', $clean['sku']) . "/" . $clean['sku']; ?>-120.jpg" class="prodImage" style="width:120px; padding:0;" title="Click to Manage Images">
                                                    </a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Screen Sequence:</td>
                                                <td><input id="screenSeq" type="text" size="2" maxlength="2" value="<?php echo $row_rsWeb['screenSeq']; ?>" onChange="setEditFlag('<?php echo $displayAction; ?>')" />
                                                    <?php echo OnlineHelp::QuickHelp('prodsite.screenSeq'); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Availability Override:</td>
                                                <td>
                                                    <select id="outOfStock" onChange="setEditFlag('<?php echo $displayAction; ?>')">
                                                        <option value="" <?php if ($row_rsWeb['outOfStock'] == '') echo "selected"; ?>>True Stock</option>
                                                        <option value="Y" <?php if ($row_rsWeb['outOfStock'] == 'Y') echo "selected"; ?>>Out of Stock</option>
                                                        <option value="N" <?php if ($row_rsWeb['outOfStock'] == 'N') echo "selected"; ?>>In Stock</option>
                                                    </select>

                                                    <?php echo OnlineHelp::QuickHelp('prod.outOfStock'); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Images:</td>
                                                <td>
                                                    <?php echo $totalRows_rsImage; ?> image(s) on file
                                                    &lt;<a href="#" onclick="loadImageMaint('<?php echo $clean['sku']; ?>')">Manage Images</a>&gt;
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Bullet1:</td>
                                                <td colspan="2">
                                                    <textarea id="bullet1" class="bullet" onFocus="preValidateSave(this.value)" onBlur="validate(this.id, this.value, 'text', 256)" onChange="setEditFlag('<?php echo $displayAction; ?>')"><?php echo htmlentities($row_rsWeb['bullet1']); ?></textarea>
                                                    <?php echo OnlineHelp::QuickHelp('prodinfo.bullets'); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Bullet2:</td>
                                                <td colspan="2">
                                                    <textarea id="bullet2" class="bullet" onFocus="preValidateSave(this.value)" onBlur="validate(this.id, this.value, 'text', 256)" onChange="setEditFlag('<?php echo $displayAction; ?>')"><?php echo htmlentities($row_rsWeb['bullet2']); ?></textarea>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Bullet3:</td>
                                                <td colspan="2">
                                                    <textarea id="bullet3" class="bullet" onFocus="preValidateSave(this.value)" onBlur="validate(this.id, this.value, 'text', 256)" onChange="setEditFlag('<?php echo $displayAction; ?>')"><?php echo htmlentities($row_rsWeb['bullet3']); ?></textarea>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Bullet4:</td>
                                                <td colspan="2">
                                                    <textarea id="bullet4" class="bullet" onFocus="preValidateSave(this.value)" onBlur="validate(this.id, this.value, 'text', 256)" onChange="setEditFlag('<?php echo $displayAction; ?>')"><?php echo htmlentities($row_rsWeb['bullet4']); ?></textarea>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Bullet5:</td>
                                                <td colspan="2">
                                                    <textarea id="bullet5" class="bullet" onFocus="preValidateSave(this.value)" onBlur="validate(this.id, this.value, 'text', 256)" onChange="setEditFlag('<?php echo $displayAction; ?>')"><?php echo htmlentities($row_rsWeb['bullet5']); ?></textarea>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Info Text:</td>
                                                <td colspan="2">
                                                    <textarea id="infoText" class="infoText" onFocus="preValidateSave(this.value)" onBlur=//"validate(this.id, this.value, 'text' , 70)" onChange="setEditFlag('<?php echo $displayAction; ?>')"><?php echo htmlentities($row_rsWeb['infoText']); ?></textarea>
                                                    <?php echo OnlineHelp::QuickHelp('prodinfo.infoText'); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Block Spiders:</td>
                                                <td colspan="2">
                                                    <input id="allowSpiders" type="checkbox" <?php if ($row_rsWeb['allowSpiders'] == 'N') echo 'checked'; ?> onChange="setEditFlag('<?php echo $displayAction; ?>')">
                                                    &nbsp;
                                                    <?php echo OnlineHelp::QuickHelp('prod.allowSpiders'); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td colspan="2">

                                                    <a tabindex="0" class="btn btn-lg btn-blue" href="#" onClick="webCancel('<?php echo $clean['sku']; ?>'); exit('<?php echo $clean['sku']; ?>');"><i class="icon-angle-left"></i> <?php if ($clean['mode'] == 'edit') echo "Cancel";
                                                                                                                                                                                                                                    else echo "OK"; ?></a>
                                                    <?php if ($clean['mode'] == 'edit') { ?>
                                                        <a tabindex="0" class="btn btn-lg btn-red" href="#" onClick="saveWebData('<?php echo $clean['sku']; ?>');"><i class="icon-check"></i> Save</a>
                                                    <?php } ?>

                                                    <br />
                                                    <div style="margin: 5px 0;">
                                                        <a href="#" onClick="popupContinue('Delete Product from Website?', 'Click Continue to delete this item from the current website.  Click back to abort.', 'delFromWebsite(\'<?php echo $clean['sku']; ?>\')', 'doNothing()')">Delete this item from website</a>
                                                    </div>
                                                </td>
                                            </tr>


                                        </table>
                                    </div>
                                    <!--box-content-->


                                </div>
                                <!--box-->
                            </div>
                            <!--col-->

                            <div class="col-md-3">
                            </div>
                            <!--col-->


                        </div>
                        <!--row-->



                    <?php
                    }
                    break;

                case 'saveWebData':
                    ?>
                    <script>
                        showWebData('<?php echo $clean['sku']; ?>');
                    </script>
                    <?php
                    break;


                case 'renderCatName':
                    if ($totalRows_rsCat == 0) {
                    ?>
                        <script>
                            validationError('Category Not On File', 'Please enter a valid Category ID', 'category', 'populatecategoryName');
                        </script>
                    <?php
                    } else {
                        echo $row_rsCat['name'];
                    }
                    break;

                case 'renderCompStockCodeName':
                    if ($totalRows_rsMotor == 0) {
                    ?>
                        <script>
                            validationError('Sku Not On File', 'Please enter a valid Sku Number', 'category', 'populatecategoryName');
                        </script>
                    <?php
                    } else {
                        echo $row_rsMotor['description'];
                    }
                    break;

                case 'customValidation':
                    if ($allOK) {
                        echo $errorText;
                    } else {
                        echo $errorText;;
                    ?>
                        <script>
                            readValResult('<?php echo $clean['field']; ?>');
                        </script>
                    <?php
                    }
                    break;


                case 'addToWebsite':
                case 'delFromWebsite':
                case 'reinstateToWebsite':
                    ?>
                    <script>
                        showData('Web', '<?php echo $clean['sku']; ?>', 'release');
                    </script>
                <?php
                    break;

                case 'webCancel':
                    break;


                case 'displayBarcodes':
                ?>
                    <table class="table table-striped">
                        <tr>
                            <td><b>Barcode:</b></td>
                            <td colspan="2"><b>Special Price:</b></td>
                            <td colspan="2">&nbsp;</td>
                        </tr>
                        <?php
                        $row = 0;
                        while ($row_rsBarcode = mysqli_fetch_assoc($rsBarcode)) {
                            $row++;
                        ?>
                            <tr>
                                <td class="row<?php echo $row % 2; ?>">
                                    <?php echo $row_rsBarcode['barcode']; ?>
                                </td>
                                <td class="row<?php echo $row % 2; ?>">
                                    <?php if ($row_rsBarcode['flashPrice'] == 0.00 && $row_rsBarcode['status'] != 'P') { ?>
                                        n/a
                                    <?php } else if ($row_rsBarcode['status'] != 'P') { ?>
                                        <?php echo $row_rsBarcode['flashPrice']; ?>
                                    <?php } else { ?>
                                    <?php } ?>
                                </td>
                                <td class="row<?php echo $row % 2; ?>">
                                    <?php if ($row_rsBarcode['status'] == 'P') { ?>
                                        <img src="images/btn_IsPrimary.gif">
                                    <?php } else if ($row_rsBarcode['status'] == 'L') { ?>
                                        Legacy Code
                                    <?php } else { ?>
                                        <a href="#" onClick="setPrimary('<?php echo $row_rsBarcode['ID']; ?>', '<?php echo $row_rsBarcode['sku']; ?>');"><img src="images/btn_SetPrimary.gif"></a>
                                    <?php } ?>
                                </td>
                                <td class="row<?php echo $row % 2; ?>">
                                    <a href="#" onClick="editBarcode('<?php echo $row_rsBarcode['ID']; ?>');" title="Edit Barcode"><img src="images/iconMaintEdit.gif"></a>
                                </td>
                                <td class="row<?php echo $row % 2; ?>">
                                    <a href="#" onClick="popupContinue('Delete this Barcode?', 'Click Continue to delete the barcode <?php echo $row_rsBarcode['barcode']; ?>.  Click Back to abort.', 'delBarcode(\'<?php echo $row_rsBarcode['ID']; ?>\', \'<?php echo $row_rsBarcode['sku']; ?>\')','doNothing()')" title="Delete Barcode"><img src="images/iconMaintDelete.gif"></a>
                                </td>
                            </tr>
                        <?php
                        } ?>
                        <tr>
                            <td colspan="5" class="right">
                                <a tabindex="0" class="btn btn-green" onClick="addBarcode('<?php echo $clean['sku']; ?>')" style="float:right; margin:10px"><i class="icon-barcode"></i> <?php if (isset($sysM_config['barcodeStack']) && $sysM_config['barcodeStack'] == 'Y') { ?>Enter New Barcode<?php } else { ?>Add Barcode<?php } ?></a>
                                <?php if (isset($sysM_config['barcodeStack']) && $sysM_config['barcodeStack'] == 'Y') { ?>
                                    <input name="btn_AddFromStack" type="button" value="Assign from Stack" onClick="assignBarcode('<?php echo $clean['sku']; ?>')" style="margin-left: 20px;" />
                                <?php } ?>
                            </td>
                        </tr>
                    </table>



                <?php
                    break;

                case 'delBarcode':
                ?>
                    <script>
                        <?php if ($tmp_NoBarcodes) { ?>
                            popup('text', 'There are no barcodes in the database for this SKU', 'No Barcodes', 'Alert');
                        <?php } ?>
                        displayBarcodes('<?php echo $clean['sku']; ?>')
                    </script>
                <?php
                    break;


                case 'setPrimary':
                case 'assignBarcode':
                ?>
                    <script>
                        displayBarcodes('<?php echo $clean['sku']; ?>')
                    </script>
                <?php
                    break;

                case 'insertBarcode':
                ?>
                    <script>
                        displayBarcodes('<?php echo $clean['sku']; ?>')
                        <?php if ($tmp_AlreadyExists) { ?>
                            validationError('Duplicate Barcode', 'The barcode <?php echo $clean['barcode']; ?> already exists in the database', '', '');
                        <?php } ?>
                    </script>
                <?php
                    break;

                case 'updBarcode':
                ?>
                    <script>
                        var stockCode = document.getElementById("stockCode").value;
                        displayBarcodes(stockCode)
                    </script>
                <?php
                    break;




                case 'editBarcode':
                ?>
                    <table class="table table-striped">
                        <tr>
                            <th colspan="5">Add Barcode</th>
                        </tr>
                        <tr>
                            <td><b>Barcode:</b></td>
                            <td><b>Special Price:</b></td>
                            <td colspan="3">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="row<?php echo $row % 2; ?>">
                                <input id="barcode" type="text" maxlength="13" value="<?php if (isset($row_rsBarcode)) echo $row_rsBarcode['barcode']; ?>" onFocus="preValidateSave(this.value)" onBlur="validate(this.id, this.value, 'int', 9999999999999)" style="width: 120px;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />
                                <?php echo OnlineHelp::QuickHelp('barcode.barcode'); ?>
                            </td>
                            <td class="row<?php echo $row % 2; ?>">
                                <?php
                                // See if a primary barcode exists yet:
                                if ($tmp_Mode == 'edit')
                                    $tmp_stockCode = $row_rsBarcode['sku'];
                                else if ($tmp_Mode == 'insert')
                                    $tmp_stockCode = $clean['sku'];
                                $query_rsCheck = "SELECT ID FROM " . $control['dbName'] . ".barcode 
						WHERE stockCode='" . $tmp_stockCode . "' AND status='P'";
                                //echo $query_rsCheck."<br>";
                                $rsCheck = ahqSql_query($query_rsCheck, $connAhq) or mysql_fail_ef();
                                $row_rsCheck = mysqli_fetch_assoc($rsCheck);
                                $totalRows_rsCheck = mysqli_num_rows($rsCheck);
                                //echo $totalRows_rsCheck." Rows<br>";

                                if ($totalRows_rsCheck > 0) { ?>
                                    <input id="flashPrice" type="text" maxlength="10" value="<?php if (isset($row_rsBarcode)) echo $row_rsBarcode['flashPrice']; ?>" onFocus="preValidateSave(this.value)" onBlur="validate(this.id, this.value, 'int', 9999999999999)" style="width: 80px;" onChange="setEditFlag('<?php echo $displayAction; ?>')" />
                                <?php } else { ?>
                                    <input id="flashPrice" type="hidden" value="" /> n/a
                                <?php } ?>
                                <?php echo OnlineHelp::QuickHelp('barcode.flashPrice'); ?>
                            </td>
                            <td class="row<?php echo $row % 2; ?>">
                                <a href="#" onClick="<?php if ($tmp_Mode == 'edit') { ?>updBarcode('<?php echo $clean['barcodeID']; ?>');<?php } else if ($tmp_Mode == 'insert') { ?>insertBarcode('<?php echo $clean['sku']; ?>');<?php } ?>" title="Edit Barcode"><img src="images/iconMaintSave.gif"></a>
                            </td>
                        </tr>
                    </table>
                    <script>
                        document.getElementById('barcode').focus();
                    </script>
                <?php
                    break;


                case 'releaseLocks':
                    break;



                case 'showMarketplacesData':
                ?>

                    <?php
                    if ($row_rsMotor['sku'] == '') {
                        dieerror("An item has to be enabled for the web before it can be forwarded to markets.", 'function', "exit('" . $clean['sku'] . "')");
                    } else {
                    ?>

                        <table class="table table-striped">
                            <tr>
                                <td style="width: 150px">Title:</td>
                                <td colspan="2"><?php echo $row_rsMotor['description']; ?></td>
                            </tr>
                            <tr>
                                <td style="width: 150px">Web Site Price:</td>
                                <td colspan="2">
                                    <?php printf("%s%.2f", $sysM_config['currSymbol'], $row_rsMotor['webPrice']); ?>
                                    (<?php printf("%.2f", $tmp_WebMargin); ?>%)
                            </tr>
                            <tr>
                                <td class="row0	 label">Ad Description:</td>
                                <td colspan="2">
                                    <p>From prodInfo:<br /><?php echo $row_rsMotor['infoText']; ?></p>

                                    <?php if ($row_rsMotor['catStatus'] == 'C' || $row_rsMotor['catStatus'] == 'P') { ?>
                                        <p>From catInfo:<br /><?php echo $row_rsMotor['narrative']; ?></p>
                                        <?php if ($row_rsMotor['narrative'] != $row_rsMotor['metaDesc']) { ?>
                                            <p><?php echo $row_rsMotor['metaDesc']; ?></p>
                                        <?php } ?>
                                    <?php } ?>

                                    Bullets:<br />
                                    <ul>
                                        <li><?php echo $row_rsMotor['bullet1']; ?></li>
                                        <li><?php echo $row_rsMotor['bullet2']; ?></li>
                                        <li><?php echo $row_rsMotor['bullet3']; ?></li>
                                        <li><?php echo $row_rsMotor['bullet4']; ?></li>
                                        <li><?php echo $row_rsMotor['bullet5']; ?></li>
                                    </ul>
                                </td>
                            </tr>



                            <tr>
                                <td class="row1 label centre">Attribute</td>
                                <td class="row1 label centre" style="width: 150px;">
                                    Amazon<br />
                                    <span style="font-weight: normal; font-size: 0.9em; ">Exclude <input id="amazonState" type="checkbox" <?php if ($row_rsMotor['amazonState'] == 'exc') echo 'checked'; ?> /></span>
                                </td>
                                <td class="row1 label centre" style="width: 150px;">
                                    Ebay<br />
                                    <span style="font-weight: normal; font-size: 0.9em; ">Exclude <input id="ebayState" type="checkbox" <?php if ($row_rsMotor['ebayState'] == 'exc') echo 'checked'; ?> /></span>
                                </td>
                            </tr>

                            <tr>
                                <td>Category 1 (from cat table):</td>
                                <td>
                                    Amazon Browse Node:<br />
                                    <?php echo $row_rsMotor['amazonCat1']; ?>
                                </td>
                                <td>
                                    ebay Category:<br />
                                    <?php echo $row_rsMotor['ebayCat1']; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Override Category:</td>
                                <td>
                                    <input id="amazonProdCat1" type="text" size="30" maxlength="50" value="<?php echo $row_rsMotor['amazonProdCat1']; ?>">
                                </td>
                                <td>
                                    <input id="ebayProdCat1" type="text" size="30" maxlength="50" value="<?php echo $row_rsMotor['ebayProdCat1']; ?>">
                                </td>
                            </tr>

                            <tr>
                                <td>Category 2 (from cat table):</td>
                                <td>
                                    Amazon Type:<br />
                                    <?php echo $row_rsMotor['amazonCat2']; ?>
                                </td>
                                <td>
                                    ebay Store Category:<br />
                                    <?php echo $row_rsMotor['ebayCat2']; ?>
                                </td>
                            </tr>
                            <tr>
                                <td>Override Category:</td>
                                <td>
                                    <input id="amazonProdCat2" type="text" size="30" maxlength="50" value="<?php echo $row_rsMotor['amazonProdCat2']; ?>">
                                </td>
                                <td>
                                    <input id="ebayProdCat2" type="text" size="30" maxlength="50" value="<?php echo $row_rsMotor['ebayProdCat2']; ?>">
                                </td>
                            </tr>

                            <tr>
                                <td>Special Price:</td>
                                <td>
                                    <input id="amazonPrice" type="text" size="10" maxlength="10" value="<?php if ($row_rsMotor['amazonPrice'] == '') echo "None";
                                                                                                        else printf("%.2f", $row_rsMotor['amazonPrice']); ?>" onBlur="updMargin('<?php echo $row_rsMotor['sku']; ?>', 'amazon', this.value)" />
                                    <span id="marg_amazon">(<?php printf("%.2f", $tmp_AmazonMargin); ?>%)</span>
                                </td>
                                <td>
                                    <input id="ebayPrice" type="text" size="10" maxlength="10" value="<?php if ($row_rsMotor['ebayPrice'] == '') echo "None";
                                                                                                        else printf("%.2f", $row_rsMotor['ebayPrice']); ?>" onBlur="updMargin('<?php echo $row_rsMotor['sku']; ?>', 'ebay', this.value)" />
                                    <span id="marg_ebay">(<?php printf("%.2f", $tmp_EbayMargin); ?>%)</span>
                                </td>
                            </tr>





                            <tr>
                                <td>Marketplace Approval Status:</td>
                                <td colspan="2">
                                    <img src="images/iconApprove<?php echo $row_rsMotor['markets']; ?>.gif" />
                                    <?php if ($row_rsMotor['markets'] == 'U') echo 'Unapproved';
                                    else if ($row_rsMotor['markets'] == 'D') echo 'Disapproved';
                                    else if ($row_rsMotor['markets'] == 'N') echo 'Approved';
                                    else echo "Unknown" ?>
                                    <strong>
                                        <?php if ($row_rsMotor['amazonState'] == 'exc') echo ' - This item has been excluded from Amazon.'; ?>
                                        <?php if ($row_rsMotor['ebayState'] == 'exc') echo ' - This item has been excluded from Ebay.'; ?>
                                    </strong>
                                </td>
                            </tr>

                            <tr>
                                <td>&nbsp;</td>
                                <td class="right">
                                    <input id="btn_Cancel" type="button" value="Cancel" onClick="exit('<?php echo $clean['sku']; ?>')" />
                                </td>
                                <td>
                                    <input id="btn_Save" type="button" value="Save" onClick="saveMarketplaceData('<?php echo $row_rsMotor['sku']; ?>')" />
                                </td>
                            </tr>


                        </table>


                        <table class="table table-striped">
                            <tr>
                                <td class="centre">
                                    <input id="btn_Approve" type="button" value="Approve" onClick="mktApprove('<?php echo $row_rsMotor['sku']; ?>')" style="width: 100px; text-align: center !important; font-weight: bold;" />
                                </td>
                            </tr>
                            <tr>
                                <td class="centre">
                                    <input id="btn_Disapprove" type="button" value="Disapprove" onClick="mktDisapprove('<?php echo $row_rsMotor['sku']; ?>')" style="width: 100px; text-align: center !important; font-weight: bold;" />
                                </td>
                            </tr>
                        </table>



                        <?php if ($row_rsMotor['markets'] == 'N') { ?>
                            <script>
                                document.getElementById('btn_Approve').disabled = true;
                            </script>
                        <?php }
                        if ($row_rsMotor['markets'] == 'D') { ?>
                            <script>
                                document.getElementById('btn_Disapprove').disabled = true;
                            </script>
                    <?php }
                    }
                    break;


                case 'mktDisapprove':
                case 'mktApprove':
                case 'saveMarketplaceData':
                    ?>
                    <script>
                        showData('Marketplaces', '<?php echo $clean['sku']; ?>', 'release');
                    </script>
                <?php
                    break;



                case 'updMargin':
                ?>
                    (<?php printf("%.2f", $tmp_Margin); ?>%)
                <?php
                    break;




                case 'showPosAttribSubData':
                ?>

                    <div class="tabMenu" style="margin: 20px 0 -19px 0; border-left: 0; width:842px; padding-left:15px;">
                        <div style="float:left;" class="tab menu"><a href="#" onclick="checkEditFlag('showData(\'ProdAttrib\', \'<?php echo $clean['sku']; ?>\', \'release\')')">Product Attributes</a></div>
                        <div style="float:left;" class="tab menu on"><a href="#" onclick="checkEditFlag('showData(\'PosAttrib\', \'<?php echo $clean['sku']; ?>\', \'release\')')">Pos Attributes</a></div>
                    </div>

                    <div class="clear" style="clear:both;">&nbsp;</div>




                    <table class="table table-striped">
                        <tr>
                            <th colspan="2">Available Attributes:</th>
                        </tr>
                        <?php
                        if ($totalRows_rsAvail == 0) {
                        ?>
                            <tr>
                                <td colspan="2">No Further Attributes Available</td>
                            </tr>
                            <?php
                        } else {
                            while ($row_rsAvail = mysqli_fetch_assoc($rsAvail)) { ?>
                                <tr>
                                    <td><?php echo $row_rsAvail['name']; ?></td>
                                    <td style="width: 25px;"><a href="#" onClick="assignAttrib('<?php echo $clean['sku']; ?>', '<?php echo $row_rsAvail['ID']; ?>')" title="Assign this Attribute"><img src="images/iconGrant.gif"></a></td>
                                </tr>
                        <?php
                                $count++;
                            }
                        }
                        ?>
                    </table>


                    <table class="table table-striped">
                        <tr>
                            <th colspan="2">Attributes Assigned:</th>
                        </tr>
                        <?php
                        if ($totalRows_rsAssigned == 0) {
                        ?>
                            <tr>
                                <td colspan="2">No Attributes Assigned Yet</td>
                            </tr>
                            <?php
                        } else {
                            while ($row_rsAssigned = mysqli_fetch_assoc($rsAssigned)) { ?>
                                <tr>
                                    <td style="width: 25px;"><a href="#" onClick="removeAttrib('<?php echo $clean['sku']; ?>', '<?php echo $row_rsAssigned['ID']; ?>')" title="Revoke this Role"><img src="images/iconRevoke.gif"></a></td>
                                    <td><?php echo $row_rsAssigned['name']; ?></td>
                                </tr>
                        <?php
                                $count++;
                            }
                        }
                        ?>
                    </table>
                <?php
                    break;




                case 'showProdAttribData':
                ?>
                    <script>
                        showData('ProdAttribSub', '30000080', 'release');
                    </script>


                    <div class="row">
                        <div class="col-md-9">

                            <div class="box">
                                <div class="box-header">
                                    <ul class="nav nav-tabs nav-tabs-left">
                                        <li class="active"><a href="#ProdAttribSubPanel" onClick="checkEditFlag('showData(\'ProdAttribSub\', \'<?php echo $clean['sku']; ?>\', \'release\')')" data-toggle="tab">Product Attributes</a></li>
                                        <li><a href="#PosAttribSubPanel" onClick="checkEditFlag('showData(\'PosAttribSub\', \'<?php echo $clean['sku']; ?>\', \'release\')')" data-toggle="tab">POS Attribs</a></li>
                                    </ul>
                                </div>

                                <div class="box-content padded">
                                    <div class="tab-content">
                                        <div class="tab-pane active" id="ProdAttribSubPanel">Prod Attribs here</div>
                                        <div class="tab-pane" id="PosAttribSubPanel">POS Atribs here<a href="#" onClick="alert()">Click Here</a></div>
                                    </div>
                                </div>

                            </div>
                            <!--box-->


                        </div>
                        <!--col-md-->

                        <div class="col-md-3">
                        </div>
                        <!--col-->


                    </div>
                    <!--row-->


                <?php
                    break;

                case 'XXshowProdAttribSubData':
                ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box">

                                <div class="box-footer padded">
                                    <a tabindex="0" class="btn btn-green" href="#" onclick="addProdAttrib1();"><i class="icon-plus"></i> Add an Attribute</a>
                                </div>

                                <table class="table table-striped">
                                    <tr>
                                        <th style="width:140px;">Name</th>
                                        <th>Value</th>
                                        <th>Type</th>
                                        <th colspan="2">&nbsp;</th>
                                    </tr>
                                    <?php
                                    $count = 0;
                                    while ($row_rsAttrib = mysqli_fetch_assoc($rsAttrib)) {
                                    ?>
                                        <tr id="attrib<?php echo $row_rsAttrib['ID']; ?>">
                                            <td class="inline"><?php echo $row_rsAttrib['description']; ?></td>
                                            <td><?php echo $row_rsAttrib['value']; ?></td>
                                            <td><?php if ($row_rsAttrib['type'] == 'D') echo 'Display';
                                                else if ($row_rsAttrib['type'] == 'F') echo 'Filter';
                                                else if ($row_rsAttrib['type'] == 'B') echo 'Both';
                                                else echo "??";; ?></td>
                                            <td class="centre"><a href="#" onclick="editProdAttrib(<?php echo $row_rsAttrib['ID']; ?>)"><img src="images/iconMaintEdit.gif" /></a></td>
                                            <td class="centre"><a href="#" onclick="popupContinue('Remove this Attribute?', 'Click Continue to remove this attrubute from this product.  Click Back to abort.', 'delProdAttrib(<?php echo $row_rsAttrib['ID']; ?>)', 'doNothing()');"><img src="images/iconMaintDelete.gif" /></a></td>
                                        </tr>
                                    <?php
                                        $count++;
                                    } ?>

                                    <tr id="newAttrib">
                                    </tr>

                                </table>
                                </table>
                            </div>
                            <!--box-content-->

                        </div>
                        <!--box-->
                    </div>
                    <!--col-->
                    </div>
                    <!--row-->


                    <script>
                        document.getElementById('btn_AddAttrib').disabled = flase;
                    </script>

                <?php
                    break;


                case 'reloadAttribs':
                ?>
                    <script>
                        showData('PosAttrib', '<?php echo $clean['sku']; ?>', 'release');
                    </script>
                <?php
                    break;



                case 'editProdAttrib':
                ?>
                    <td label">
                        <?php echo $row_rsAttrib['description']; ?>
                    </td>
                    <td>
                        <input id="attribID" type="hidden" value="<?php echo $row_rsAttrib['ID']; ?>">
                        <input id="attribValue" type="text" value="<?php echo $row_rsAttrib['value']; ?>">
                        <input id="stockCode" type="hidden" value="<?php echo $row_rsAttrib['sku']; ?>">

                    </td>
                    <td><?php if ($row_rsAttrib['type'] == 'D') echo 'Display';
                        else if ($row_rsAttrib['type'] == 'F') echo 'Filter';
                        else if ($row_rsAttrib['type'] == 'B') echo 'Both';
                        else echo "??";; ?></td>
                    <td centre" colspan="2"><a href="#" onclick="saveProdAttrib()"><img src="images/iconMaintSave.gif" /></a></td>

                    <script>
                        document.getElementById('attribValue').focus();
                    </script>
                <?php
                    break;



                case 'saveProdAttrib':
                case 'addProdAttrib3':
                case 'delProdAttrib':
                ?>
                    <script>
                        showData('ProdAttrib', '<?php echo $clean['sku']; ?>', 'release');
                    </script>
                <?php
                    break;


                case 'addProdAttrib1':
                ?>
                    <td label">
                        <select id="attribName">
                            <?php while ($row_rsAttrib = mysqli_fetch_assoc($rsAttrib)) { ?>
                                <option value="<?php echo $row_rsAttrib['attribName']; ?>"><?php echo $row_rsAttrib['description']; ?></option>
                            <?php } ?>
                        </select>
                    </td>

                    <td>
                        <input name="btn_AddAttrib1" type="button" value="Add Attribute &gt;" onclick="addProdAttrib2()" />
                    </td>

                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>

                    <script>
                        document.getElementById('btn_AddAttrib').disabled = true;
                    </script>

                    <?php
                    break;




                case 'addProdAttrib2':
                    if ($row_rsAttrib['multi'] == 'N' && $totalRows_rsAttrib2 > 0) {
                    ?>
                        <script>
                            popup('text', 'You are only allowed one instance of the <?php echo $clean['attribName']; ?> attribute', '<?php echo $clean['attribName']; ?> Attribute Exists', 'Alert');
                            addProdAttrib1();
                        </script>
                    <?php } else { ?>

                        <td>
                            <?php echo $clean['attribName']; ?>
                            <input type="hidden" id="attribName" value="<?php echo $clean['attribName']; ?>" />
                        </td>

                        <td>

                            <?php if ($row_rsAttrib['open'] == 'Y') { ?>
                                <input type="text" id="value" />
                            <?php } else { ?>
                                <select id="value">
                                    <?php while ($row_rsAttrib3 = mysqli_fetch_assoc($rsAttrib3)) { ?>
                                        <option value="<?php echo $row_rsAttrib3['value']; ?>"><?php echo $row_rsAttrib3['value']; ?></option>
                                    <?php } ?>
                                </select>
                            <?php } ?>

                        </td>

                        <td>
                            <!--<input name="btn_AddAttrib1" type="button" value="Add Attribute &gt;" onclick="addProdAttrib2" />-->
                            &nbsp;
                        </td>

                        <td><a href="#" onclick="addProdAttrib3()"><img src="images/iconMaintSave.gif" /></a></td>

                        <td>&nbsp;</td>

                        <script>
                            document.getElementById('value').focus();
                        </script>

                    <?php
                    }
                    break;



                case 'saveSource':
                    break;


                case 'updCost':
                    ?>
                    Current Cost for this SKU: <?php echo $sysM_config['currSymbol'] . $clean['cost']; ?>
                    <input name="btn_UpdCost" value="Update Cost" type="button" disabled="disabled" />

                <?php
                    break;



                case 'showImageData':
                ?>

                    <input type="hidden" id="sku" value="<?php echo $clean['sku']; ?>">

                    <div class="row imagepanel">
                        <div class="col-md-12">

                            <div class="box">

                                <div class="box-header d-flex justify-content-between">
                                    <div class="title bolder2">
                                        Images:
                                        <?php echo $totalRows_rsImage; //."^".$totalRows_rsImageR;
                                        ?> on file
                                        <?php if ($totalRows_rsImageR == 0) { ?>

                                        <?php } else { ?>
                                            :
                                            <?php echo $totalRows_rsImageR; ?> being processed
                                            <i class="fa fa-spinner fa-spin"></i>
                                            <?php /*
								<span>[<a href="#ImagePanel" onclick="checkEditFlag('showData(\'Image\', \'<?php echo $clean['sku'];?>\', \'release\')')" data-toggle="tab">Refresh</a>]</span>
								*/ ?>
                                            <br>
                                            <span>The processing of images occurs on the server and you can safely browse away from this page
                                                <!--<?php echo (60 - date('s')); ?>--></span>
                                        <?php } ?>
                                    </div>

                                    <?php if ($totalRows_rsImage > 0) { ?>
                                        <div class="box-link d-flex">
                                            <ul class="box-toolbar">
                                                <li class="toolbar-link highlight">
                                                    <a href="#" onclick="popupContinue('Delete ALL Images?', 'Click Continue to delete all images for this vehicle, or click Back to abort.', 'delAllImages(\'<?php echo $clean['sku']; ?>\')', 'doNothing()')">
                                                        Delete All Images <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    <?php } ?>

                                </div>

                                <div class="box-content">

                                    <ul class="tagsinput">

                                        <?php
                                        $count = 0;
                                        $galleryPath = getCarImagePath();
                                        $galleryHttp = $sysB_client['url'] . "/img/cars/";

                                        if ($totalRows_rsImage == 0) {
                                        ?>
                                            <li class="tag" style="padding:0 5px;">There are currently no images for this vehicle</li>
                                            <?php
                                        } else {


                                            while ($row_rsImage = mysqli_fetch_assoc($rsImage)) {

                                                $imageSize = getimagesize($galleryPath . $row_rsImage['imageName'] . '-largest.jpg');

                                                if (
                                                    // This condition prevents us from attempting to display an image before it has been processed
                                                    ($row_rsImage['screenSeq'] <= 1 && file_exists($galleryPath . $row_rsImage['imageName'] . '-largest.jpg'))
                                                    ||
                                                    ($row_rsImage['screenSeq'] > 1 && file_exists($galleryPath . $row_rsImage['imageName'] . '-' . themePrefix() . 'thumb.jpg'))

                                                ) {
                                            ?>
                                                    <li class="tag" draggable=true>
                                                        <a href="#" data-toggle="tooltip" data-placement="top" title="<?php echo $row_rsImage['screenSeq'] . '/' . $row_rsImage['imageNum']; ?> - Original Image Name: <?php echo $row_rsImage['sourceName']; ?>, Size:<?php echo $imageSize[0] . 'x' . $imageSize[1]; ?>px, ID:<?php echo $row_rsImage['ID']; ?>">
                                                            <img src="<?php echo $galleryHttp . $row_rsImage['imageName']; ?>-<?php if ($row_rsImage['screenSeq'] <= 1) echo "largest";
                                                                                                                            else echo themePrefix() . "thumb"; ?>.jpg" <?php if ($row_rsImage['screenSeq'] <= 1) echo "class=\"primary\""; ?> />
                                                        </a>
                                                        <a id="option<?php echo $row_rsImage['ID']; ?>" href="#" onClick="popupContinue('Delete this Image?', 'Click Continue to delete it or click Back to abort.', 'delImage(<?php echo $row_rsImage['ID']; ?>)', 'doNothing()')" title="Removing tag"><i class="fa fa-times" aria-hidden="true"></i></a>
                                                    </li>
                                        <?php
                                                }

                                                $count++;
                                            }
                                        }

                                        if ($totalRows_rsImage > 0)
                                            mysqli_data_seek($rsImage, 0);

                                        ?>


                                    </ul>


                                    <script>
                                        <?php // Sortable tiles code 
                                        ?>

                                        $(function() {

                                            var start_index = '';
                                            var end_index = '';
                                            var sku = $("#sku").val();

                                            $(".tagsinput").sortable({
                                                containment: "parent",
                                                cursor: 'move',
                                                connectWith: ".tagsinput",
                                                tolerance: "pointer",

                                                start: function(event, ui) {
                                                    console.log("start position: " + ui.item.index())
                                                    start_index = ui.item.index();

                                                    //alert("start position: " + ui.item.index());
                                                },
                                                update: function(event, ui) {
                                                    //debugger
                                                    //alert("New position: " + ui.item.index());
                                                    console.log("end position: " + ui.item.index())
                                                    end_index = ui.item.index();
                                                    //call function change

                                                    var temp = [];
                                                    $.map($(this).find('li'), function(el) {
                                                        //   console.log(el)
                                                        var obj = $(el).find('a').attr('id')
                                                        temp.push(obj)
                                                        //console.log($(el).find('a').attr('id') + ' = ' + $(el).index());
                                                    })
                                                    //console.log('temp')
                                                    //console.log(temp)
                                                    var option_id = temp[end_index];
                                                    changeImgPos(sku, start_index, end_index);
                                                    //change(option_id ,start_index,end_index);
                                                    start_index = '';
                                                    end_index = '';
                                                }

                                            });

                                            $(".tagsinput").disableSelection();
                                        });



                                        $(document).on('click', '#close-preview', function() {
                                            $('.image-preview').popover('hide');
                                            // Hover befor close the preview
                                            $('.image-preview').hover(
                                                function() {
                                                    $('.image-preview').popover('show');
                                                },
                                                function() {
                                                    $('.image-preview').popover('hide');
                                                }
                                            );
                                        });


                                        <?php // Fancy Uploader 
                                        ?>

                                        $(function() {
                                            // Create the close button
                                            var closebtn = $('<button/>', {
                                                type: "button",
                                                text: 'x',
                                                id: 'close-preview',
                                                style: 'font-size: initial;',
                                            });
                                            closebtn.attr("class", "close pull-right");
                                            // Set the popover default content
                                            $('.image-preview').popover({
                                                trigger: 'manual',
                                                html: true,
                                                title: "<strong>Preview</strong>" + $(closebtn)[0].outerHTML,
                                                content: "There's no image",
                                                placement: 'bottom'
                                            });
                                            // Clear event
                                            $('.image-preview-clear').click(function() {
                                                $('.image-preview').attr("data-content", "").popover('hide');
                                                $('.image-preview-filename').val("");
                                                $('.image-preview-clear').hide();
                                                $('.image-preview-input input:file').val("");
                                                $(".image-preview-input-title").text("Browse");
                                            });
                                            // Create the preview image
                                            $(".image-preview-input input:file").change(function() {
                                                var img = $('<img/>', {
                                                    id: 'dynamic',
                                                    width: 250,
                                                    height: 200
                                                });
                                                var file = this.files[0];
                                                var reader = new FileReader();
                                                // Set preview image into the popover data-content
                                                reader.onload = function(e) {
                                                    // $(".image-preview-input-title").text("Change");
                                                    $(".image-preview-clear").show();
                                                    //  $(".image-preview-filename").val(file.name);
                                                    //img.attr('src', e.target.result);
                                                    //$(".image-preview").attr("data-content",$(img)[0].outerHTML).popover("show");
                                                }
                                                //get the input and UL list
                                                var input = document.getElementById('filesToUpload');
                                                var list = document.getElementById('show-all-files-cont');

                                                /*
                                                for (var x = 0; x < input.files.length; x++) {

                                                    $(".image-preview-input-title").text("Change");
                                                    $(".image-preview-filename").val(x+" Files Added")
                                                }
                                                */

                                                $(".image-preview-filename").val(input.files.length + " File(s) Added")

                                                reader.readAsDataURL(file);
                                            });
                                        });



                                        <?php if ($totalRows_rsImageR > 0) { ?>


                                            function sleep(time) {
                                                return new Promise((resolve) => setTimeout(resolve, time));
                                            }

                                            // Usage!
                                            sleep(5000).then(() => {
                                                // Do something after the sleep!

                                                showData('Image', '<?php echo $clean['sku']; ?>', 'release')
                                            })



                                        <?php } ?>
                                    </script>

                                </div>
                                <!--box-content-->

                            </div>
                            <!--box-->



                            <?php if (isset($sysM_dms['3pSite']) && $sysM_dms['3pSite'] == 'Y') { ?>
                                <div class="box">
                                    <div class="box-content" style="margin-bottom:15px">
                                        <table class="table table-striped">
                                            <tr>
                                                <th class="boxHead">Retrieve Primary Image from Website</th>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <p>Enter URL:</p>
                                                            <div class="d-flex">
                                                                <div class="imageuploadwrap">
                                                                    <div class="input-group image-preview" data-original-title="" title="">
                                                                        <div id="show-all-files-cont">
                                                                            <input type="text" class="form-control" style="height:30px;" id="url" value="<?= $MotorInfo->getUrl() ?>">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="upload-btn">
                                                                    <a href="#" class="btn btn-info" onClick="retrievePicsFromSite('<?php echo $clean['sku']; ?>')">Retrieve Data</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div id="retrieveInfo">
                                                                <div class="alert alert-info">
                                                                    Enter a URL from Website
                                                                </div>
                                                            </div>

                                                        </div>
                                                        <!--col--->
                                                    </div>
                                                    <!--row-->
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            <?php } ?>

                            <div class="box">
                                <div class="box-content" style="margin-bottom:15px">
                                    <table class="table table-striped">
                                        <tr>
                                            <th class="boxHead">Add Images</th>
                                        </tr>
                                        <tr>
                                            <td>

                                                <form enctype="multipart/form-data" method="post" action="?page=vehicle-maint&sku=<?php echo $clean['sku']; ?>&tab=Image&mode=edit">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <p>Select Images to Upload:</p>
                                                            <div class="d-flex">
                                                                <div class="imageuploadwrap">
                                                                    <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $sysC_globalMotor['maxUploadGigs'] * 1000000; ?>">
                                                                    <input type="hidden" name="page" value="stk-image-maint">
                                                                    <div class="input-group image-preview" data-original-title="" title="">
                                                                        <div id="show-all-files-cont">
                                                                            <input type="text" class="form-control image-preview-filename" id="show-all-files" style="height:30px;" disabled="disabled"> <!-- don't give a name === doesn't send on POST/GET -->
                                                                        </div>
                                                                        <div class="input-group-btn">
                                                                            <!-- image-preview-clear button -->
                                                                            <button type="button" class="btn btn-default image-preview-clear" style="display:none;">
                                                                                <span class="glyphicons glyphicons-remove"></span> Clear
                                                                            </button>
                                                                            <!-- image-preview-input -->
                                                                            <div class="btn btn-default image-preview-input">
                                                                                <i class="mdi mdi-folder-open"></i>
                                                                                <span class="image-preview-input-title">Browse</span>
                                                                                <input type="file" accept="image/png, image/jpeg, image/gif" id="filesToUpload" name="userfile[]" multiple=""> <!-- rename it -->
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div><!-- imageuploadwrap ends -->
                                                                <div class="upload-btn">
                                                                    <input type="submit" class="btn btn-info" value="Upload File">
                                                                </div><!-- upload-btn ends -->
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="alert alert-info">
                                                                You can upload multiple images and one 360 photo
                                                            </div>
                                                        </div>
                                                        <!--col--->
                                                    </div>
                                                    <!--row-->
                                                </form>

                                            </td>
                                        </tr>
                                    </table>
                                </div>


                            </div><!-- box -->


                            <div class="box">

                                <div class="box-content" style="margin-bottom:15px">
                                    <table class="table table-striped">
                                        <tr>
                                            <th class="boxHead">360 Image</th>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php if ($Motor->get360Image() != 'Y') { ?>
                                                    No 360 image exists for this vehicle
                                                <?php } else if (isset($tmp_360Timestamp) && $tmp_360Timestamp != '') { ?>
                                                    Uploaded <?php echo $tmp_360Timestamp; ?>
                                                    <button class="btn btn-mini btn-red" onClick="delete360('<?php echo $clean['sku']; ?>')">Delete</button>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                            </div>



                            <div class="box addurl">

                                <div class="box-content">
                                    <table class="table table-striped">
                                        <tr>
                                            <th class="boxHead">Add Video</th>
                                        </tr>
                                        <tr>
                                            <td>

                                                <form enctype="multipart/form-data" method="post" action="?page=vehicle-maint&sku=<?php echo $clean['sku']; ?>&tab=Image&mode=edit" style="margin:0; padding:0;">
                                                    <div class="row">
                                                        <!--col starts--->
                                                        <div class="col-xl-4 col-lg-3 col-md-3 urltext1">
                                                            <p><b>Enter Video URL:</b></p>
                                                            <input type="text" id="videoUrl" style="width:100%;" value="<?php echo $Motor->getVideoUrl(); ?>">
                                                        </div>
                                                        <div class="col-xl-2 col-lg-2 col-md-2 urlbtn1">
                                                            <input type="button" class="btn btn-info urlbtn" value="Save Video URL" onclick="saveVideoUrl()">
                                                            <span id="videoUrlSaved"></span>
                                                        </div>
                                                        <div class="col-xl-6 col-lg-7 col-md-7">
                                                            <div class="alert alert-info">
                                                                <p>You can define one video URL</p>
                                                                <p>It must be submitted in YouTube's 'embed' format, e.g.: http://www.youtube.com/embed/HHkdprQ6Ruo?wmode=transparent</p>
                                                            </div>
                                                        </div>
                                                        <!--col ends--->
                                                    </div>
                                                </form>

                                            </td>
                                        </tr>
                                    </table>
                                </div>

                            </div>

                            <script>
                                updTabs('Image', '<?php echo $clean['sku']; ?>');
                            </script>


                        <?php
                        break;


                    case 'delAllImages':
                    case 'imageMove':
                    case 'delImage':
                        if ($displayAction == 'delAllImages')
                            $tmp_sku = $clean['sku'];
                        if ($displayAction == 'imageMove')
                            $tmp_sku = $row_rsImage['sku'];
                        ?>
                            <script>
                                showData('Image', '<?php echo $tmp_sku; ?>', 'release');
                            </script>
                        <?php
                        break;



                    case 'changeImgPos':
                    case 'assignPrimaryImage':
                    case 'saveVideoUrl':
                        ?>
                            <script>
                                showData('Image', '<?php echo $clean['sku']; ?>', 'release');
                            </script>
                        <?php
                        break;



                    case 'assignPrimaryImage':
                        ?>
                            <script>
                                showData('Image', '<?php echo $clean['sku']; ?>', 'release');
                            </script>
                        <?php
                        break;





                    case 'anprWizStep1':
                        ?>
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                <h4 class="modal-title" id="modalTitle">Enter Price & Mileage</h4>
                            </div>

                            <div class="modal-body anprChoice" id="modalBody">



                                <div class="row">
                                    <div class="col-md-12">

                                        <div class="alert alert-block left">
                                            <strong><i class="fa fa-camera" aria-hidden="true"></i> Nearly there!</strong><br>
                                            <button type="button" class="close" data-dismiss="alert">×</button>
                                            Enter a price and a mileage to make this vehicle ready to sell online.
                                            If you ommit the price the vehicle will be listed as '£ TBA'.
                                            You can add or change the price at any time.
                                        </div>


                                        <div class="box">

                                            <div class="box-content">

                                                <table class="table table-striped">

                                                    <tr>
                                                        <td>Price:</td>
                                                        <td>
                                                            <input id="wiz1Price" type="text" maxlength="6" style="text-align: left;" onChange="setEditFlag('<?php //echo $displayAction;
                                                                                                                                                                ?>')" />
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Mileage:</td>
                                                        <td>
                                                            <input id="wiz1Mileage" type="text" maxlength="6" style="text-align: left;" onChange="setEditFlag('<?php //echo $displayAction;
                                                                                                                                                                ?>')" />
                                                        </td>
                                                    </tr>

                                                </table>

                                            </div>
                                            <!--box-content-->

                                        </div>
                                        <!--box-->

                                        <button class="btn btn-large btn-block btn-green ahqBtnMarg" onclick="anprWizStep2('<?php echo $clean['sku']; ?>')">Next ></button>


                                    </div>
                                    <!--col-->
                                </div>
                                <!--row-->


                            </div>
                            <div class="modal-footer">
                                <p class="centre">Select a suggested vehicle above or enter the VRM directly</p>
                            </div>

                        <?php
                        break;



                    case 'anprWizStep2':
                        ?>

                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                <h4 class="modal-title" id="modalTitle">Enter price & Mileage</h4>
                            </div>

                            <div class="modal-body anprChoice" id="modalBody">


                                <div class="alert alert-block left">
                                    <strong><i class="fa fa-camera" aria-hidden="true"></i> Nearly there!</strong>
                                    <button type="button" class="close" data-dismiss="alert">×</button>
                                    This vehicle is now listed on your website. Choose the following options:
                                </div>


                                <div class="box">

                                    <div class="box-content">

                                        <table class="table table-striped">

                                            <tr>
                                                <td>
                                                    <a class="btn btn-large btn-block btn-blue ahqBtnMarg" href="?page=vehicle-maint&sku=<?php echo $clean['sku']; ?>&tab=Image">Add Photos</a>
                                                </td>
                                                <td>
                                                    <a class="btn btn-large btn-block btn-blue ahqBtnMarg" href="?page=vehicle-maint&sku=<?php echo $clean['sku']; ?>">Edit Data</a>
                                                </td>
                                            </tr>

                                        </table>

                                    </div>
                                    <!--box-content-->

                                </div>
                                <!--box-->

                                <div class="anprChoice">
                                    <a href="?page=vehicle-new&goto=anprStart" class="btn btn-large btn-block btn-green ahqBtnMarg">Add Another Vehicle by ANPR</a>
                                </div>

                            </div>



                        <?php
                        break;


                    case 'update360':
                        ?>
                            <script>
                                $("#alertPanel").hide();
                                showData('Image', '<?php echo $clean['sku']; ?>', '');
                            </script>
                        <?php
                        break;


                    case 'delete360':
                        ?>
                            <script>
                                showData('Image', '<?php echo $clean['sku']; ?>', '');
                            </script>
                        <?php
                        break;




                    case 'fbSuccess':

                        //var_dump($_POST);
                        $query_rsStatus = "SELECT * FROM a0_common.names WHERE type='motorStatus' AND status='N' AND code!='T' ORDER BY screenSeq";
                        $rsStatus = ahqSql_query($query_rsStatus, $connAhq) or mysql_fail_ef();
                        //if(isset($_SESSION['debug']) && $_SESSION['debug']=='on')
                        //	echo $query_rsStatus."<br>";

                        break;




                    case 'essentialModal':

                        require_once($siteDir_Class . "/EssentialModal.php");
                        $EssentialModal = new EssentialModal();

                        ?>
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                <h4 class="modal-title" id="modalTitle">
                                    Essential Data:
                                    <span>
                                        <?php echo $Motor->getMake(); ?> <?php echo $Motor->getModel(); ?>
                                        <?php echo $Motor->getVariant(); ?> <?php echo $Motor->getReg(); ?>
                                    </span>
                                </h4>
                            </div>

                            <div class="modal-body" id="modalBody">

                                <div class="row">

                                    <?php
                                    $EssentialModal->GetRequiredColumns($Motor->getStatus(), $Motor->getType());
                                    $EssentialModal->InputControls();
                                    ?>

                                </div>


                                <?php if (isset($totalRows_rsAttribHead) && $totalRows_rsAttribHead > 0) { ?>
                                    <div id="attribs">Attribs</div>
                                    <script>
                                        showAttribsInEssentials('<?php echo $clean['sku']; ?>');
                                    </script>
                                <?php } ?>


                                <div class="row">
                                    <div class="btnCont">
                                        <a tabindex="0" class="btn btn-lg btn-blue" data-dismiss="modal"><i class="icon-angle-left"></i> Cancel</a>
                                        <a tabindex="0" class="btn btn-lg btn-red" href="#" onclick="saveEssentialData('<?php echo $clean['sku']; ?>')"><i class="icon-check"></i> Save</a>
                                    </div>
                                </div>

                            </div>
                        <?php
                        break;



                    case 'receiptModal':

                        require_once($siteDir_Class . "/EssentialModal.php");
                        $EssentialModal = new EssentialModal();

                        ?>
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                <h4 class="modal-title" id="modalTitle">
                                    Receive Vehicle:
                                    <span>
                                        <?php echo $Motor->getMake(); ?> <?php echo $Motor->getModel(); ?>
                                        <?php echo $Motor->getVariant(); ?> <?php echo $Motor->getReg(); ?>
                                    </span>
                                </h4>
                            </div>

                            <div class="modal-body" id="modalBody">

                                <div class="row">

                                    <?php

                                    $EssentialModal->InputControls('single', 'supplierID');
                                    $EssentialModal->InputControls('single', 'receiptDate');
                                    $EssentialModal->InputControls('single', 'keySafeNum');
                                    $EssentialModal->InputControls('single', 'numberOfKeys');
                                    $EssentialModal->InputControls('single', 'mileage');
                                    ?>


                                </div>
                                <div class="row">
                                    <div class="btnCont">
                                        <a tabindex="0" class="btn btn-lg btn-blue" data-dismiss="modal"><i class="icon-angle-left"></i> Cancel</a>
                                        <a tabindex="0" class="btn btn-lg btn-red" href="#" onclick="saveReceipt('<?php echo $clean['sku']; ?>')"><i class="icon-check"></i> Save</a>
                                    </div>
                                </div>

                            </div>
                        <?php
                        break;

                    case 'renderv5c':
                    case 'renderservice':
                        ?>
                            <script>
                                ahqAlert('Document Uploaded');
                                showData('Admin', '<?php echo $clean['sku']; ?>', '');
                            </script>
                            <?php
                            break;


                        case 'renderMotCert':
                        case 'renderV5cFront':
                        case 'renderV5cInner':
                        case 'renderConditionRpt':
                        case 'renderPdiReport':
                        case 'renderServiceHistDoc':

                            require_once($siteDir_Class . "/EssentialModal.php");
                            $EssentialModal = new EssentialModal();
                            $EssentialModal->InputControls('single', lowerFirstLetter($tmp_DocType), 'noLabel');

                            break;


                        case 'retrievePicsFromSite':

                            if ($result['status']) {
                            ?>
                                <script>
                                    ahqAlert('Vehicle Data Updated');
                                    showData('Image', '<?php echo $clean['sku']; ?>', '');
                                </script>
                            <?php
                            } else {
                            ?>
                                <div class="alert alert-danger">
                                    <b>Error:</b><br>
                                    <?= $result['error'] ?>
                                </div>
                            <?php
                            }

                            break;




                        case 'startReadyForSale':
                            ?>
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                <h4 class="modal-title" id="modalTitle">
                                    Ready for Sale:
                                    <span>
                                        <?php echo $Motor->getMake(); ?> <?php echo $Motor->getModel(); ?>
                                        <?php echo $Motor->getVariant(); ?> <?php echo $Motor->getReg(); ?>
                                    </span>
                                </h4>
                            </div>


                            <div class="modal-body" id="modalBody">

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="alert alert-info" style="width: 100%">
                                            If this vehicle is ready for sale?
                                        </div>
                                    </div>
                                </div>


                                <div class="row">
                                    <?php $EssentialModal->InputControls('single', 'showInWebsite'); ?>
                                    <?php $EssentialModal->InputControls('single', 'pdiPass'); ?>
                                </div>



                                <div class="row">
                                    <div class="btnCont">
                                        <a tabindex="0" class="btn btn-lg btn-blue" data-dismiss="modal"><i class="icon-angle-left"></i> Cancel</a>
                                        <a tabindex="0" class="btn btn-lg btn-red" href="#" onclick="setReadyForSale('<?php echo $clean['sku']; ?>')"><i class="icon-check"></i> Save</a>
                                    </div>
                                </div>

                            </div>
                        <?php
                            break;



                        case 'setReadyForSale':
                        ?>
                            <script>
                                //document.getElementById('editFlag').innerHTML = 'Clear';
                                //updTabs('Basic', '<?php echo $clean['sku']; ?>');

                                ahqAlert('Vehicle Status Updated');
                                showTopData('<?php echo $clean['sku']; ?>');
                                showData('Basic', '<?php echo $clean['sku']; ?>', 'release');
                                $("#modal-support").modal("hide");
                            </script>
                        <?php
                            break;



                        case 'updateMargin':

                            echo number_format(calcMargin($clean['price'], $clean['cost'], $clean['vatCode']), 2) . "% Margin";

                            break;




                        case 'mileageHistory':
                        ?>
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                <h4 class="modal-title" id="modalTitle">
                                    Mileage History:
                                    <span>
                                        <?php //echo $Motor->getMake();
                                        ?> <?php //echo $Motor->getModel();
                                                                            ?>
                                        <?php //echo $Motor->getVariant();
                                        ?> <?php //echo $Motor->getReg();
                                                                                ?>
                                    </span>
                                </h4>
                            </div>


                            <div class="modal-body" id="modalBody">

                                <div class="row">
                                    <div class="col-md-12">
                                        <table class="table table-striped">
                                            <tr>
                                                <th>Date/Time</th>
                                                <th>Mileage</th>
                                                <th>Event</th>
                                                <th>User</th>
                                            </tr>

                                            <?php while ($row_rsMileage = mysqli_fetch_assoc($rsMileage)) { ?>
                                                <tr>
                                                    <td><?= sql_timestamp_to_date_or_time($row_rsMileage['updateTime']) ?></td>
                                                    <td><?= $row_rsMileage['value'] ?></td>
                                                    <td><?= capFirstLetter($row_rsMileage['event']) ?></td>
                                                    <td><?= $row_rsMileage['fullName'] ?></td>
                                                </tr>
                                            <?php } ?>

                                        </table>
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="btnCont">
                                        <a tabindex="0" class="btn btn-lg btn-blue" data-dismiss="modal"><i class="icon-angle-left"></i> Close</a>

                                    </div>
                                </div>

                            </div>
                        <?php
                            break;


                        case 'viewDocs':
                        ?>
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                <h4 class="modal-title" id="modalTitle">
                                    Documents On File:
                                    <span>
                                    </span>
                                </h4>
                            </div>

                            <div class="modal-body" id="modalBody">

                                <div class="row">

                                    <?php

                                    while ($row_rsMotorDoc = mysqli_fetch_assoc($rsMotorDoc)) {



                                        //arrayDUmp($row_rsMotorDoc);

                                        if ($row_rsMotorDoc['fileType'] == 'J')
                                            $tmp_Ext = ".jpg";
                                        else if ($row_rsMotorDoc['fileType'] == 'P')
                                            $tmp_Ext = ".pdf";

                                        $tmp_FileName = $tmp_FileStem . "-" . $row_rsMotorDoc['subDocNum'] . $tmp_Ext;

                                        //echo $tmp_DocPath.$tmp_FileName."<br>";

                                    ?>
                                        <div class="col-4">
                                            <div class="docCont">
                                                <a href="<?= $tmp_DocPath . $tmp_FileName ?>" target="_blank">
                                                    <img src="<?= $tmp_DocPath . $tmp_FileName ?>">
                                                </a>
                                                <div class="docNav">
                                                    <!--<div class="click" onClick="moveDoc(<?= $row_rsMotorDoc['ID'] ?>, '<?= $clean['docType'] ?>', -1)"><i class="fas fa-chevron-left"></i></div>-->
                                                    <div class="click" onClick="delDoc(<?= $row_rsMotorDoc['ID'] ?>, '<?= $clean['docType'] ?>')"><i class="fas fa-trash-alt"></i></div>
                                                    <!--<div class="click" onClick="moveDoc(<?= $row_rsMotorDoc['ID'] ?>, '<?= $clean['docType'] ?>', +1)"><i class="fas fa-chevron-right"></i></div>-->
                                                </div>
                                            </div>
                                        </div>
                                    <?php

                                    }


                                    ?>

                                </div>



                            </div>
                        <?php
                            break;


                        case 'delDoc':
                        case 'moveDoc':

                            echo $tmp_DocType . "<br>"

                            //die("Error: *** - Execution ceased in ".basename(__FILE__)." Line ". __LINE__.$newLine);

                        ?>

                            <script>
                                var sku = $("#sku").html();

                                //alert('sku:'+sku)

                                <?php

                                if (!isset($totalRows_rsMotorDoc) || $totalRows_rsMotorDoc > 0) {


                                ?>

                                    viewDocs(sku, '<?= $tmp_DocType ?>');
                                    //alert('3900')

                                <?php } else { ?>

                                    $('#modal-support').modal('toggle');

                                <?php } ?>

                                <?php // Refresh the Admin tab regardless 
                                ?>

                                showData('Admin', sku, 'release');
                            </script>

                        <?php
                            break;



                        case 'showInfoData':

                        ?>

                            <div class="row">
                                <div class="col-md-12">

                                    <div class="box">

                                        <div class="box-content">

                                            <table class="table table-striped">
                                                <?php if ($Motor->getLegacySku() != '') { ?>
                                                    <tr>
                                                        <td class="label">Legacy SKU: </td>
                                                        <td>
                                                            <?php echo $Motor->getLegacySku(); ?>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                                <tr>
                                                    <td class="label">Time Created: </td>
                                                    <td>
                                                        <?php echo SQL_to_UK_DateTime($Motor->getCreated()); ?>
                                                    </td>
                                                </tr>

                                                <?php if ($Motor->getCapID() != '') { ?>
                                                    <tr>
                                                        <td class="label">CAP ID: </td>
                                                        <td>
                                                            <?php echo $Motor->getCapID(); ?>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                                <?php if ($Motor->getCapCode() != '') { ?>
                                                    <tr>
                                                        <td class="label">CAP Code: </td>
                                                        <td>
                                                            <?php echo $Motor->getCapCode(); ?>
                                                        </td>
                                                    </tr>
                                                <?php } ?>

                                                <?php if ($Motor->getSolddate() != '') { ?>
                                                    <tr>
                                                        <td class="label">Date Sold: </td>
                                                        <td>
                                                            <?php echo SQL_to_UK_Date($Motor->getSolddate()); ?>
                                                        </td>
                                                    </tr>
                                                <?php } ?>

                                                <?php if ($Motor->getDeletedate() != '') { ?>
                                                    <tr>
                                                        <td class="label">Flagged for Deletion: </td>
                                                        <td>
                                                            <?php echo SQL_to_UK_Date($Motor->getDeletedate()); ?>
                                                        </td>
                                                    </tr>
                                                <?php } ?>

                                                <?php if ($Motor->getMotExpiry() != '') { ?>
                                                    <tr>
                                                        <td class="label">Last Mot Test: </td>
                                                        <td>
                                                            <?php echo SQL_to_UK_Date($Motor->getMotExpiry()); ?>
                                                            (<?php if ($Motor->getMotLastResult() == 'P') echo 'Pass';
                                                                else echo 'Fail'; ?>)
                                                            <?php if ($Motor->getMotLookupTime() != '') { ?>
                                                                <?= $Motor->getMotLookupTime() ?>
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                                <?php } ?>

                                            </table>

                                        </div>
                                        <!--box-content-->

                                    </div>
                                    <!--box-->

                                </div>
                                <!--col-->

                            </div>
                            <!--row-->


                        <?php

                            break;



                        default:
                            echo "Unknown display action '<b>" . $displayAction . "</b>' (" . basename(__FILE__) . " Line " . __LINE__ . ")<br>";
                            break;
                    }


                    // If lock has just been created do this for all actions:
                    if (isset($tmp_LockStatus) && $tmp_LockStatus == 'Locked') {
                        ?><script>
                            document.getElementById('lockStatus').innerHTML = 'Locked';
                        </script><?php
                                } else if (isset($tmp_LockStatus) && $tmp_LockStatus == 'Released') {
                                    ?><script>
                            document.getElementById('lockStatus').innerHTML = 'Released';
                        </script><?php
                                }


                                    ?>