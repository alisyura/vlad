<?php

interface MiddlewareInterface {
    function handle(): bool;
}