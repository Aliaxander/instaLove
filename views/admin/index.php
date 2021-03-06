<table class="table table-striped table-hover">
	<thead>
	<tr class="active">
		<td>id</td>
		<td>userName</td>
		<td>proxy</td>
		<td>status</td>
		<td>task</td>
		<td>progress</td>
		<td></td>
	</tr>
	</thead>
	<tbody>
    <?php

    use app\models\Status;

    if (!empty($users)) {
        foreach ($users as $user) {
            ?>
			<tr>
				<td><?= $user->id ?></td>
				<td><a href="https://instagram.com/<?= $user->userName ?>" target="_blank"><?= $user->userName ?></a> (<?= $followers[$user->id] ?>)</td>
				<td><?= $user->proxy ?></td>
				<td><?= Status::findIdentity($user->status) ?></td>
				<td style="width: 220px;">
                    <?= $user->task ?>
				</td>
				<td>
                    
                    <?php
                    if ($progress[$user->id]) {
                        ?>
						<div class="progress">
							<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
							     aria-valuenow="<?= $progressBar[$user->id] ?>" aria-valuemin="0" aria-valuemax="100"
							     style="width: <?= $progressBar[$user->id] ?>%">
								<span><?= $progress[$user->id] ?></span>
							</div>
						</div>
                        
                        <?php
                    }
                    ?>
				</td>
				<td style="width: 240px;">
                    <?php
                    if (in_array($taskIdAll[$user->id], [13, 11, 9, 7, 5, 3])) {
                        ?>
						<a class="btn btn-xs btn-default" href="/admin/reload-task/?id=<?= $user->id ?>"><i
									class="fa fa-refresh" title="reload task"
									aria-hidden="true"></i></a>
                        <?php
                    }
                    ?>
					<a class="btn btn-xs btn-default" href="/admin/edit/?id=<?= $user->id ?>"><i
								class="fa fa-pencil"
								aria-hidden="true"></i></a>
					<a class="btn btn-xs btn-default" href="/admin/scheduler/?id=<?= $user->id ?>"><i
								class="fa fa-calendar"
								aria-hidden="true"></i></a>
					<a class="btn btn-xs btn-default" href="/admin/check/?id=<?= $user->id ?>"><i
								class="fa fa-users" aria-hidden="true"></i></a>
					<a class="btn btn-xs btn-danger"
					   onclick="return confirm('Confirm delere user ID: <?= $user->id ?>');"
					   href="/admin/del/?id=<?= $user->id ?>"><i class="fa fa-trash" aria-hidden="true"></i></a>
				
				</td>
			</tr>
            <?php
        }
    }
    ?>
	</tbody>
</table>
<a href="/admin/addbot" class="btn btn-default">Add account</a>
</div>
</div>