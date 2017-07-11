<div class="site-index">
	<div class="container">
		<table class="table table-striped table-hover">
			<thead>
			<tr class="active">
				<td>id</td>
				<td>email</td>
				<td>userName</td>
				<td>proxy</td>
				<td>status</td>
				<td>task</td>
				<td></td>
			</tr>
			</thead>
			<tbody>
            <?php
            use app\models\Status;
            use app\models\Task;
            
            if (!empty($users)) {
                foreach ($users as $user) {
                    ?>
					<tr>
						<td><?= $user->id ?></td>
						<td><?= $user->email ?></td>
						<td><?= $user->userName ?></td>
						<td><?= $user->proxy ?></td>
						<td><?= Status::findIdentity($user->status) ?></td>
						<td style="width: 220px;"><?= Task::findIdentity($user->task) ?></td>
						<td style="width: 140px;">
                            <?php
                            if ($user->task == 1) {
                                ?>
								<a class="btn btn-xs btn-default" href="/admin/start/?task=2&id=<?= $user->id ?>"><i
											class="fa fa-play"
											aria-hidden="true"></i></a>
                                <?php
                            } else {
                                ?>
								<a class="btn btn-xs btn-default" href="/admin/start/?task=1&id=<?= $user->id ?>"><i
											class="fa fa-pause"
											aria-hidden="true"></i></a>
                                <?php
                            }
                            ?>
							<a class="btn btn-xs btn-default" href="/admin/edit/?id=<?= $user->id ?>"><i
										class="fa fa-pencil"
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