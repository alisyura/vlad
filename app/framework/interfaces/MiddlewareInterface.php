<?php

// app/interfaces/MiddlewareInterface.php
interface MiddlewareInterface {
    function handle(?array $params = null): bool;
}