This template is used for translation message extraction tests
<?php echo $view['translator']->trans('single-quoted key'); ?>
<?php echo $view['translator']->trans('double-quoted key'); ?>
<?php echo $view['translator']->trans(<<<'EOF'
heredoc key
EOF
); ?>
<?php echo $view['translator']->trans(<<<'EOF'
nowdoc key
EOF
); ?>
<?php echo $view['translator']->trans(
    "double-quoted key with whitespace and escaped \$\n\" sequences"
); ?>
<?php echo $view['translator']->trans(
    'single-quoted key with whitespace and nonescaped \$\n\' sequences'
); ?>
<?php echo $view['translator']->trans(<<<EOF
heredoc key with whitespace and escaped \$\n sequences
EOF
); ?>
<?php echo $view['translator']->trans(<<<'EOF'
nowdoc key with whitespace and nonescaped \$\n sequences
EOF
); ?>

<?php echo $view['translator']->trans('single-quoted key with "quote mark at the end"'); ?>

<?php echo $view['translator']->transChoice(
    '{0} There is no apples|{1} There is one apple|]1,Inf[ There are %count% apples',
    10,
    array('%count%' => 10)
); ?>

<?php echo $view['translator']->trans('other-domain-test-no-params-short-array', array(), 'not_messages'); ?>

<?php echo $view['translator']->trans('other-domain-test-no-params-long-array', array(), 'not_messages'); ?>

<?php echo $view['translator']->trans('other-domain-test-params-short-array', array('foo' => 'bar'), 'not_messages'); ?>

<?php echo $view['translator']->trans('other-domain-test-params-long-array', array('foo' => 'bar'), 'not_messages'); ?>

<?php echo $view['translator']->transChoice('other-domain-test-trans-choice-short-array-%count%', 10, array('%count%' => 10), 'not_messages'); ?>

<?php echo $view['translator']->transChoice('other-domain-test-trans-choice-long-array-%count%', 10, array('%count%' => 10), 'not_messages'); ?>

<?php echo $view['translator']->trans('typecast', array('a' => (int) '123'), 'not_messages'); ?>
<?php echo $view['translator']->transChoice('msg1', 10 + 1, array(), 'not_messages'); ?>
<?php echo $view['translator']->transChoice('msg2', ceil(4.5), array(), 'not_messages'); ?>
