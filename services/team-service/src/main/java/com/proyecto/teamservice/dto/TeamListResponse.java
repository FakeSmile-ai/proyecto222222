package com.proyecto.teamservice.dto;

import java.util.List;

public record TeamListResponse(
        List<TeamResponse> items,
        long totalCount,
        int page,
        int pageSize
) {
}
