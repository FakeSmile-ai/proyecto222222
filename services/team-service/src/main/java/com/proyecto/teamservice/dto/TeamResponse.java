package com.proyecto.teamservice.dto;

import java.util.List;

public record TeamResponse(
        Integer id,
        String name,
        String color,
        Integer playersCount,
        List<PlayerSummary> players
) {
}
