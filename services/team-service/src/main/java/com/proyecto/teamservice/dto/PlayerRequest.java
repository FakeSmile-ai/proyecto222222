package com.proyecto.teamservice.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.PositiveOrZero;

public record PlayerRequest(
        @PositiveOrZero Integer number,
        @NotBlank String name
) { }
