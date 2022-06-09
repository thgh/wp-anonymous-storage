<style>
.tbl {
  border: 1px solid #ccc;
  border-spacing: 0px;
  border-collapse: collapse;
}

.tbl tr {
  border: 1px solid #ccc;
}

.tbl td,
.tbl th {
  border: 1px solid #ccc;
  padding: 10px;
  text-align: left;
}

.invalid {
  color: #c00;
  font-weight: 600
}
</style>
<h1>Bewaarde doelen</h1>
<?php

echo '<table class="tbl">';
echo '<thead>';
echo '<tr>';
echo '<th>Date</th>';
echo '<th>Author</th>';
echo '<th>Goals</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($as_links as $index => $link) {
  $value = json_decode($link->value);
  echo '<tr>';
  echo '<td>';
  echo $link->updated_at;
  echo '</td>';
  echo '<td>';
  echo $value->author ?? $link->author;
  echo '</td>';
  echo '<td>';
  if (!empty($value->selection)) {
    foreach ($value->selection as $index => $goal) {
      if ($index>0) echo '<br/>';
      echo $goal->title;
    }
  } else {
    echo '<span class="invalid">Invalid!!!</span>';
  }
  echo '</td>';
  echo '</tr>';
}
echo '</tbody>';
echo '</table>';