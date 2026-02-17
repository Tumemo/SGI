import BannerGlobal from '../components/BannerGlobal/BannerGlobal'
import NavBar from '../components/NavBar/NavBar'
import TextTop from '../components/TextTop/TextTop'

function NovaEdicao(){
    return(
        <>
        <BannerGlobal titulo="Nova Edição"/>
        <TextTop textTop="Escolha uma modalidade para editar"/>
        <NavBar />
        </>
    )
}

export default NovaEdicao