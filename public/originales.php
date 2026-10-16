<?php
/** Antes «Originales»: desde la migra-159 (16/10/2026) es el foro «originales» de la seccion Foros. Se mantiene para enlaces y marcadores antiguos. */
header('Location: /foros?f=originales' . (isset($_GET['p']) ? '&p=' . (int)$_GET['p'] : ''), true, 301);
