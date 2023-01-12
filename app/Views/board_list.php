    <table class="table">
		<thead>
			<tr>
			<th scope="col">번호</th>
			<th scope="col">글쓴이</th>
			<th scope="col">제목</th>
			<th scope="col">등록일</th>
			</tr>
		</thead>
		<tbody id="board_list">
			<?php
			$idNumber = $total - ($page-1)*$perPage;
			foreach($list as $ls){
			?>
				<tr>
					<th scope="row"><?php echo $idNumber--;?></th>
					<td><?php echo $ls->userid;?></td>
					<td><a href="/boardView/<?php echo $ls->bid;?>"><?php echo $ls->subject;?></a>
					<?php if($ls->filecnt){?>
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-card-image" viewBox="0 0 16 16">
						<path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/>
						<path d="M1.5 2A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13zm13 1a.5.5 0 0 1 .5.5v6l-3.775-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12v.54A.505.505 0 0 1 1 12.5v-9a.5.5 0 0 1 .5-.5h13z"/>
						</svg>
					<?php }?>
					<?php if($ls->memocnt){?>
						<span <?php if((time()-strtotime($ls->memodate))<=86400){ echo "style='color:red;'";}?>>
							[<?php echo $ls->memocnt;?>]
						</span>
					<?php }?>
					<?php if($ls->newid){?>
						<span class="badge bg-danger">New</span>
					<?php }?>
					</td>
					<td><?php echo $ls->regdate;?></td>
				</tr>
			<?php }?>
		</tbody>
		</table>
		<!-- 페이징 -->
		<div style="padding-top:30px;">
			<?= $pager_links ?>
		</div>
		<p style="text-align:right;">
			<a href="/boardWrite"><button type="button" class="btn btn-primary">등록</button><a>
			<?php 
			if(isset($_SESSION['userid'])){
			?>
				<a href="/logout"><button type="button" class="btn btn-warning">로그아웃</button><a>
			<?php }else{?>
				<a href="/login"><button type="button" class="btn btn-warning">로그인</button><a>
			<?php }?>
		</p>