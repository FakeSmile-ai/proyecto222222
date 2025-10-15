package com.proyecto.teamservice.service;

import com.fasterxml.jackson.core.type.TypeReference;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.proyecto.teamservice.dto.PlayerPayload;
import com.proyecto.teamservice.dto.PlayerRequest;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.*;
import org.springframework.stereotype.Component;
import org.springframework.web.client.RestTemplate;

import java.util.Collections;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

@Component
public class PlayerServiceClient {

    private static final Logger log = LoggerFactory.getLogger(PlayerServiceClient.class);

    private final RestTemplate restTemplate;
    private final ObjectMapper mapper = new ObjectMapper();
    private final String baseUrl;

    public PlayerServiceClient(@Value("${player.service.base-url:http://player-service:80}") String baseUrl,
                               RestTemplate restTemplate) {
        this.baseUrl = baseUrl.endsWith("/") ? baseUrl.substring(0, baseUrl.length() - 1) : baseUrl;
        this.restTemplate = restTemplate;
    }

    public int countPlayersByTeam(int teamId) {
        String url = String.format("%s/players?teamId=%d&page=1&pageSize=1", baseUrl, teamId);
        try {
            ResponseEntity<String> response = restTemplate.getForEntity(url, String.class);
            if (response.getStatusCode().is2xxSuccessful() && response.getBody() != null) {
                Map<String, Object> map = mapper.readValue(response.getBody(), new TypeReference<>() {});
                Number total = (Number) map.getOrDefault("totalCount", 0);
                return total.intValue();
            }
        } catch (Exception ex) {
            log.error("Error fetching players count from player-service", ex);
        }
        return 0;
    }

    public List<PlayerPayload> getPlayersByTeam(int teamId) {
        String url = String.format("%s/players?teamId=%d&page=1&pageSize=1000", baseUrl, teamId);
        try {
            ResponseEntity<String> response = restTemplate.getForEntity(url, String.class);
            if (response.getStatusCode().is2xxSuccessful() && response.getBody() != null) {
                Map<String, Object> map = mapper.readValue(response.getBody(), new TypeReference<>() {});
                Object rawItems = map.get("items");
                if (rawItems != null) {
                    String json = mapper.writeValueAsString(rawItems);
                    return mapper.readValue(json, new TypeReference<>() {});
                }
            }
        } catch (Exception ex) {
            log.error("Error fetching players list from player-service", ex);
        }
        return Collections.emptyList();
    }

    public void createPlayers(int teamId, List<PlayerRequest> players) {
        if (players == null || players.isEmpty()) {
            return;
        }
        Map<String, Object> payload = new HashMap<>();
        payload.put("teamId", teamId);
        payload.put("players", players);
        postJson("/players/bulk", payload, HttpMethod.POST);
    }

    public void replacePlayers(int teamId, List<PlayerRequest> players) {
        Map<String, Object> payload = new HashMap<>();
        payload.put("teamId", teamId);
        payload.put("players", players);
        postJson("/players/bulk", payload, HttpMethod.PUT);
    }

    public void deleteByTeam(int teamId) {
        String url = String.format("%s/players/by-team/%d", baseUrl, teamId);
        try {
            restTemplate.delete(url);
        } catch (Exception ex) {
            log.error("Error deleting players by team in player-service", ex);
        }
    }

    private void postJson(String path, Map<String, Object> payload, HttpMethod method) {
        String url = baseUrl + path;
        HttpHeaders headers = new HttpHeaders();
        headers.setContentType(MediaType.APPLICATION_JSON);
        HttpEntity<Map<String, Object>> entity = new HttpEntity<>(payload, headers);
        try {
            restTemplate.exchange(url, method, entity, String.class);
        } catch (Exception ex) {
            log.error("Error calling {} on player-service", path, ex);
        }
    }
}
