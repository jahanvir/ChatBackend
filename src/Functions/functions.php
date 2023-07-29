<?php

function generateRandomToken()
{
    return bin2hex(random_bytes(32));
}
