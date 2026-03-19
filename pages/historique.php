<?php
requireLogin();
if($user['role']==='commercant'){
$s=$db->prepare('SELECT t.*,u.prenom as cp,u.nom as cn FROM transactions t JOIN users u ON t.user_id=u.id WHERE t.commercant_id=? ORDER BY t.created_at DESC LIMIT 50');
$s->execute([$user['id']]);
}else{
$s=$db->prepare('SELECT t.*,u.prenom as cp,u.nom as cn FROM transactions t LEFT JOIN users u ON t.commercant_id=u.id WHERE t.user_id=? ORDER BY t.created_at DESC LIMIT 50');
$s->execute([$user['id']]);
}$tr=$s->fetchAll(PDO::FETCH_ASSOC);?>
<div class="card"><h2>Historique des transactions</h2>
<?php if(empty($tr)):?><p style="color:rgba(255,255,255,0.3);text-align:center;padding:40px">Aucune transaction</p>
<?php else:?><table><thead><tr><th>Date</th><th>Type</th><th>Montant</th><th>Description</th></tr></thead><tbody>
<?php foreach($tr as $t):?><tr>
<td><?=date('d/m/Y H:i',strtotime($t['created_at']))?></td>
<td class="<?=$t['type']==='credit'?'type-credit':'type-paiement'?>"><?=$t['type']==='credit'?'Credit':'Paiement'?></td>
<td style="font-weight:600"><?=$t['type']==='credit'?'+':'-'?><?=number_format($t['montant'],2)?> EUR</td>
<td><?=htmlspecialchars($t['description'])?></td>
</tr><?php endforeach;?></tbody></table><?php endif;?></div>
