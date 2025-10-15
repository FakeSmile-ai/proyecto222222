package com.proyecto.teamservice.service;

import com.proyecto.teamservice.dto.*;
import com.proyecto.teamservice.model.Team;
import com.proyecto.teamservice.repository.TeamRepository;
import jakarta.persistence.EntityNotFoundException;
import jakarta.transaction.Transactional;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.PageRequest;
import org.springframework.data.domain.Pageable;
import org.springframework.data.domain.Sort;
import org.springframework.stereotype.Service;

import java.util.List;
import java.util.stream.Collectors;

@Service
public class TeamService {

    private final TeamRepository repository;
    private final PlayerServiceClient playerClient;

    public TeamService(TeamRepository repository, PlayerServiceClient playerClient) {
        this.repository = repository;
        this.playerClient = playerClient;
    }

    public TeamListResponse list(int page, int pageSize, String search) {
        int size = Math.max(1, Math.min(pageSize, 100));
        int pageIndex = Math.max(page, 1) - 1;
        Pageable pageable = PageRequest.of(pageIndex, size, Sort.by("name"));

        Page<Team> result;
        if (search != null && !search.isBlank()) {
            result = repository.findByNameContainingIgnoreCase(search.trim(), pageable);
        } else {
            result = repository.findAll(pageable);
        }

        List<TeamResponse> items = result.getContent().stream()
                .map(this::mapToResponseWithoutPlayers)
                .collect(Collectors.toList());

        return new TeamListResponse(items, result.getTotalElements(), pageIndex + 1, size);
    }

    public TeamResponse getById(int id) {
        Team team = repository.findById(id)
                .orElseThrow(() -> new EntityNotFoundException("Team not found"));
        List<PlayerPayload> players = playerClient.getPlayersByTeam(team.getId());
        List<PlayerSummary> summaries = players.stream()
                .map(p -> new PlayerSummary(p.id(), p.number(), p.name()))
                .toList();
        return new TeamResponse(team.getId(), team.getName(), team.getColor(), summaries.size(), summaries);
    }

    @Transactional
    public TeamResponse create(TeamRequest request) {
        Team team = new Team();
        team.setName(request.name().trim());
        team.setColor(request.color() == null || request.color().isBlank() ? null : request.color().trim());
        Team saved = repository.save(team);

        if (request.players() != null && !request.players().isEmpty()) {
            playerClient.createPlayers(saved.getId(), request.players());
        }

        int count = playerClient.countPlayersByTeam(saved.getId());
        return new TeamResponse(saved.getId(), saved.getName(), saved.getColor(), count, null);
    }

    @Transactional
    public void update(int id, TeamRequest request) {
        Team team = repository.findById(id)
                .orElseThrow(() -> new EntityNotFoundException("Team not found"));

        team.setName(request.name().trim());
        team.setColor(request.color() == null || request.color().isBlank() ? null : request.color().trim());
        repository.save(team);

        if (request.players() != null) {
            playerClient.replacePlayers(team.getId(), request.players());
        }
    }

    @Transactional
    public void delete(int id) {
        if (!repository.existsById(id)) {
            throw new EntityNotFoundException("Team not found");
        }
        repository.deleteById(id);
        playerClient.deleteByTeam(id);
    }

    private TeamResponse mapToResponseWithoutPlayers(Team team) {
        int count = playerClient.countPlayersByTeam(team.getId());
        return new TeamResponse(team.getId(), team.getName(), team.getColor(), count, null);
    }
}
