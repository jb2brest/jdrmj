"""
Tests pour vérifier que les fixtures fonctionnent correctement
"""
import pytest

class TestFixtures:
    """Tests des fixtures de base"""
    
    def test_test_user_fixture(self, test_user):
        """Test de la fixture test_user"""
        assert test_user['username'] == 'test_user'
        assert test_user['email'] == 'test@example.com'
        assert test_user['password'] == 'TestPassword123!'
        assert test_user['is_dm'] == True
    
    def test_test_character_fixture(self, test_character):
        """Test de la fixture test_character"""
        assert test_character['name'] == 'Test Character'
        assert test_character['race'] == 'Humain'
        assert test_character['class'] == 'Guerrier'
        assert test_character['level'] == 1
        assert test_character['background'] == 'Soldat'
    
    def test_test_campaigns_fixture(self, test_campaigns):
        """Test de la fixture test_campaigns"""
        assert 'main_campaign' in test_campaigns
        assert 'public_campaign' in test_campaigns
        assert test_campaigns['main_campaign']['name'] == 'Test Campaign'
        assert test_campaigns['public_campaign']['is_public'] == True
    
    def test_test_monsters_fixture(self, test_monsters):
        """Test de la fixture test_monsters"""
        assert 'dragon' in test_monsters
        assert 'goblin' in test_monsters
        assert test_monsters['dragon']['name'] == 'Dragon Rouge'
        assert test_monsters['goblin']['challenge_rating'] == 0.25
    
    def test_test_magical_items_fixture(self, test_magical_items):
        """Test de la fixture test_magical_items"""
        assert 'sword' in test_magical_items
        assert 'potion' in test_magical_items
        assert test_magical_items['sword']['type'] == 'Arme'
        assert test_magical_items['potion']['rarity'] == 'Commune'
    
    def test_test_poisons_fixture(self, test_poisons):
        """Test de la fixture test_poisons"""
        assert 'basic_poison' in test_poisons
        assert 'deadly_poison' in test_poisons
        assert test_poisons['basic_poison']['dc'] == 10
        assert test_poisons['deadly_poison']['dc'] == 15
    
    def test_app_url_fixture(self, app_url):
        """Test de la fixture app_url"""
        assert app_url == 'http://localhost/jdrmj'
    
    def test_browser_config_fixture(self, browser_config):
        """Test de la fixture browser_config"""
        assert 'headless' in browser_config
        assert 'window_size' in browser_config
        assert 'implicit_wait' in browser_config
