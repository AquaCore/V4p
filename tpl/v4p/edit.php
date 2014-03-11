<?php
/**
 * @var $top   \V4p\Top
 * @var $form  \Aqua\UI\Form
 * @var $token string
 * @var $page  \Page\Admin\V4p
 */
$form->prepend(
	'<div style="text-align: center;">' .
	'<div class="ac-delete-wrapper">' .
	'<img src="' . $top->imageUrl . '">' .
	($top->image ? '<a href="' . ac_build_url(array(
				'path' => array( 'v4p' ),
				'action' => 'edit',
				'arguments' => array( $top->id ),
				'query' => array(
					'token' => $token,
					'x-action' => 'delete-image'
				)
			)) . '"><button type="button" class="ac-delete-button"></button></a>' : '') .
	'</div></div>'
);
$form->field('submit')->attr('class', 'ac-button');
echo $form->render();
