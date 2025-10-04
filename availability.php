<?php
echo "<pre>";
print_r($_POST);



?>
 <form method="post">
<table>
<tr>
<th>date</th>
<th>AM</th>
<th>PM</th>
</tr>
<tr>
<td>monday</td>
<td><input type="time" id="date_monday_am" name="date_monday_am"></td>
<td><input type="time" id="date_monday_pm" name="date_monday_pm"></td>
</tr>
<tr>
<td>tuesday</td>
<td><input type="time" id="date_tue_am" name="date_tue_am"></td>
<td><input type="time" id="date_tue_pm" name="date_tue_pm"></td>
</tr>
<tr>
<td>wednesday</td>
<td>AM</td>
<td>PM</td>
</tr>
<tr>
<td>thursday</td>
<td>AM</td>
<td>PM</td>
</tr>
<tr>
<td>friday</td>
<td>AM</td>
<td>PM</td>
</tr>
<tr>
<td>saturday</td>
<td>AM</td>
<td>PM</td>
</tr>
<tr>
<td>sunday</td>
<td>AM</td>
<td>PM</td>
</tr>

</table>
<button type="submit">Add</button>
    </form>

