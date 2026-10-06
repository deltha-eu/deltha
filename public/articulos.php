<?php
/** Antes «Artículos»: ahora es Originales. Se mantiene para enlaces y marcadores antiguos. */
header('Location: /originales' . (isset($_GET['p']) ? '?p=' . (int)$_GET['p'] : ''), true, 301);
