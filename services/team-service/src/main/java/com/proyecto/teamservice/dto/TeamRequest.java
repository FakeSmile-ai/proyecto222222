package com.proyecto.teamservice.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.Size;

import java.util.List;

public record TeamRequest(
        @NotBlank @Size(max = 100) String name,
        @Size(max = 20) String color,
        List<PlayerRequest> players
) {
}
