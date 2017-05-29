<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/house_manage.php') == false) {
	//Acess denied
	echo "<div class='error'>" ;
		echo __('You do not have access to this action.');
	echo "</div>" ;
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__('Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__(getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/house_manage.php'>".__('Manage Houses')."</a> > </div><div class='trailEnd'>".__('Assign Houses').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($_GET['return'], null, null);
    }

    $gibbonYearGroupIDList = (isset($_GET['gibbonYearGroupIDList']))? $_GET['gibbonYearGroupIDList'] : '';
    $gibbonHouseIDList = (isset($_GET['gibbonHouseIDList']))? $_GET['gibbonHouseIDList'] : '';
    $balanceYearGroup = (isset($_GET['balanceYearGroup']))? $_GET['balanceYearGroup'] : '';

    $gibbonYearGroupIDList = explode(',', $gibbonYearGroupIDList);

    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'today' => date('Y-m-d'));
        $sql = "SELECT gibbonYearGroup.gibbonYearGroupID, gibbonHouse.name AS house, gibbonHouse.gibbonHouseID, gibbonYearGroup.name as yearGroupName, count(gibbonStudentEnrolment.gibbonPersonID) AS total, count(CASE WHEN gibbonPerson.gender='M' THEN gibbonStudentEnrolment.gibbonPersonID END) as totalMale, count(CASE WHEN gibbonPerson.gender='F' THEN gibbonStudentEnrolment.gibbonPersonID END) as totalFemale
                FROM gibbonHouse
                    LEFT JOIN gibbonPerson ON (gibbonPerson.gibbonHouseID=gibbonHouse.gibbonHouseID
                        AND gibbonPerson.status='Full'
                        AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)
                        AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today) )
                    LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID
                        AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID)
                    LEFT JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
                GROUP BY gibbonYearGroup.gibbonYearGroupID, gibbonHouse.gibbonHouseID
                ORDER BY gibbonYearGroup.sequenceNumber, gibbonHouse.name";

        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    echo '<h3>';
    echo __('Assign Houses');
    echo '</h3>';

    if ($result->rowCount() == 0) {
        echo '<div class="error">';
        echo __('There are no records to display.');
        echo '</div>';
    } else {

        $count = (isset($_GET['count']))? $_GET['count'] : 0;

        echo '<p>';
        echo sprintf(__('%1$s students were successfully assigned to houses.'), $count);
        echo '</p>';

        $yearGroups = $result->fetchAll(\PDO::FETCH_GROUP);
        $headings = array_column(current($yearGroups), 'house');

        echo '<table cellspacing="0" style="width: 100%">';
        echo '<tr class="head">';
        echo '<th style="width: 20%">';
        echo __('Year Group');
        echo '</th>';

        foreach ($headings as $house) {
            echo '<th style="width: '.(80 / count($headings)).'%">';
            echo __($house);
            echo '</th>';
        }
        echo '</tr>';

        foreach ($yearGroups as $gibbonYearGroupID => $rowData) {

            $row = current($rowData);
            $rowClass = (!in_array($gibbonYearGroupID, $gibbonYearGroupIDList))? 'dull' : '';

            echo '<tr class="'.$rowClass.'">';

            echo '<td>';
            echo $row['yearGroupName'];
            echo '</td>';

            foreach ($rowData as $data) {
                echo '<td>';
                echo '<span title="'.$data['totalFemale'].' '.__('Female').'<br/>'.$data['totalMale'].' '.__('Male').'">';
                echo $data['total'];
                echo '</span>';
                echo '</td>';
            }
            echo '</tr>';

        }
        echo '</table>';
    }
}
