<?php
header('Content-Type: application/manifest+json; charset=utf-8');
readfile(__DIR__ . '/manifest.json');
